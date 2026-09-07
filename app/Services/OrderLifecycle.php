<?php
namespace App\Services;

use PDO;
use RuntimeException;

final class OrderLifecycle
{
    public function __construct(private PDO $db) {}

    private function order(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: throw new RuntimeException('Commande introuvable.');
    }

    private function transaction(callable $action): void
    {
        $this->db->beginTransaction();
        try { $action(); $this->db->commit(); }
        catch (\Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    private function history(int $id, string $status, string $note): void
    {
        $this->db->prepare('INSERT INTO order_status_history(order_id,status,note) VALUES(?,?,?)')->execute([$id,$status,mb_substr($note,0,255)]);
    }

    public function updateStatus(int $id, string $status, string $note): void
    {
        $this->transaction(function () use ($id,$status,$note) {
            $order = $this->order($id);
            if ($status === $order['status']) return;
            if ($status === 'cancelled' && mb_strlen(trim($note)) < 5) throw new RuntimeException('Indiquez un motif d’annulation d’au moins 5 caractères.');
            if ($order['returned_at'] && $status !== 'cancelled') throw new RuntimeException('Une commande retournée ne peut pas être livrée de nouveau.');
            $allowed = ['pending'=>['processing','cancelled'], 'paid'=>['processing','shipping','completed','cancelled'], 'processing'=>['shipping','completed','cancelled'], 'shipping'=>['completed','cancelled'], 'completed'=>['cancelled'], 'cancelled'=>[]];
            if (!in_array($status, $allowed[$order['status']] ?? [], true)) throw new RuntimeException('Transition de commande non autorisée. Une annulation ne constitue pas un remboursement.');
            if (in_array($status,['processing','shipping','completed'],true) && !in_array($order['payment_status'],['paid','cod'],true)) throw new RuntimeException('Le paiement doit être confirmé avant la préparation.');
            if ($status === 'completed') {
                if ($order['payment_status'] !== 'paid') throw new RuntimeException('Enregistrez d’abord l’encaissement à la livraison.');
                $this->moveStock($id, false);
                $this->db->prepare('UPDATE orders SET delivered_at=NOW() WHERE id=?')->execute([$id]);
            }
            $this->db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status,$id]);
            if ($status === 'cancelled') $this->revokeAccess($id,false);
            $this->history($id,$status,$note ?: 'Statut logistique mis à jour');
        });
    }

    // Net movements also cover legacy orders whose stock was debited at payment.
    private function moveStock(int $id, bool $return): void
    {
        $stmt = $this->db->prepare("SELECT oi.item_id,SUM(oi.quantity) quantity FROM order_items oi JOIN products p ON p.id=oi.item_id WHERE oi.order_id=? AND oi.item_type='product' AND p.product_type='physical' GROUP BY oi.item_id ORDER BY oi.item_id");
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $item) {
            $stock = $this->db->prepare('SELECT stock_quantity FROM products WHERE id=? FOR UPDATE');
            $stock->execute([$item['item_id']]); $before = (int) $stock->fetchColumn();
            $moves = $this->db->prepare("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE order_id=? AND product_id=? AND movement_type IN ('sale','return')");
            $moves->execute([$id,$item['item_id']]); $net = (int) $moves->fetchColumn();
            $quantity = $return ? max(0,-$net) : -max(0,(int)$item['quantity'] + $net);
            if (!$quantity) continue;
            $after = $before + $quantity;
            if ($after < 0) throw new RuntimeException('Stock insuffisant pour confirmer cette livraison.');
            $this->db->prepare("UPDATE products SET stock_quantity=?,stock_status=CASE WHEN ?=0 THEN 'out_of_stock' WHEN stock_status='out_of_stock' THEN 'in_stock' ELSE stock_status END WHERE id=?")->execute([$after,$after,$item['item_id']]);
            $this->db->prepare('INSERT INTO stock_movements(product_id,order_id,movement_type,quantity,stock_before,stock_after,note) VALUES(?,?,?,?,?,?,?)')->execute([$item['item_id'],$id,$return?'return':'sale',$quantity,$before,$after,$return?'Articles retournés, reçus et remis en stock':'Livraison ou retrait confirmé']);
        }
    }

    public function receiveReturn(int $id, string $note, bool $restock = true): void
    {
        if ($note === '') throw new RuntimeException('Précisez le contrôle effectué sur les articles retournés.');
        $this->transaction(function () use ($id,$note,$restock) {
            $order = $this->order($id);
            if ($order['returned_at']) return;
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE order_id=? AND movement_type IN ('sale','return')");
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() >= 0) throw new RuntimeException('Aucun article sorti du stock pour cette commande.');
            if ($restock) $this->moveStock($id,true);
            $this->db->prepare('UPDATE orders SET returned_at=NOW() WHERE id=?')->execute([$id]);
            $this->history($id,$order['status'],($restock?'Retour reçu et remis en stock : ':'Retour reçu, non remis en stock : ').$note);
        });
    }

    public function requestRefund(int $id, string $reason): void
    {
        if (mb_strlen($reason) < 5) throw new RuntimeException('Indiquez le motif du remboursement.');
        $this->transaction(function () use ($id,$reason) {
            $order = $this->order($id);
            if ($order['payment_status'] !== 'paid' || (float)$order['total'] <= 0) throw new RuntimeException('Seule une commande encaissée peut être remboursée.');
            $stmt=$this->db->prepare('SELECT status FROM order_refunds WHERE order_id=? FOR UPDATE');$stmt->execute([$id]);$current=$stmt->fetchColumn();
            if ($current === 'requested') return;
            if ($current === 'completed') throw new RuntimeException('Cette commande a déjà été remboursée.');
            if ($current === 'rejected') $this->db->prepare("UPDATE order_refunds SET status='requested',reason=?,requested_at=NOW(),admin_note=NULL WHERE order_id=?")->execute([$reason,$id]);
            else $this->db->prepare("INSERT INTO order_refunds(order_id,amount,reason) VALUES(?,?,?)")->execute([$id,$order['total'],$reason]);
            $this->history($id,$order['status'],'Demande de remboursement intégral : '.$reason);
        });
    }

    private function revokeAccess(int $id, bool $refunded): void
    {
        $this->db->prepare("UPDATE enrollments SET status='cancelled',payment_status=IF(?,'refunded',payment_status) WHERE order_id=?")->execute([$refunded?1:0,$id]);
        $this->db->prepare("UPDATE enrollments e JOIN (SELECT o.user_id,oi.item_id,MAX(o.id) other_id FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.payment_status='paid' AND o.status<>'cancelled' AND oi.item_type='course' GROUP BY o.user_id,oi.item_id) x ON x.user_id=e.user_id AND x.item_id=e.course_id SET e.order_id=x.other_id,e.payment_status='paid',e.status=IF(e.completed_at IS NULL,'active','completed') WHERE e.order_id=?")->execute([$id]);
    }

    public function rejectRefund(int $id, string $reason): void
    {
        if (mb_strlen(trim($reason)) < 5) throw new RuntimeException('Précisez le motif de refus du remboursement.');
        $this->transaction(function () use ($id,$reason) {
            $order=$this->order($id);
            $stmt=$this->db->prepare("UPDATE order_refunds SET status='rejected',admin_note=? WHERE order_id=? AND status='requested'");
            $stmt->execute([$reason,$id]);
            if (!$stmt->rowCount()) throw new RuntimeException('Aucune demande de remboursement en attente pour cette commande.');
            $this->history($id,$order['status'],'Remboursement refusé : '.$reason);
        });
    }

    public function completeRefund(int $id, string $reference, string $method, string $note): void
    {
        if ($reference === '' || $method === '') throw new RuntimeException('La référence et le moyen du remboursement réellement exécuté sont obligatoires.');
        $this->transaction(function () use ($id,$reference,$method,$note) {
            $order = $this->order($id);
            $stmt=$this->db->prepare('SELECT * FROM order_refunds WHERE order_id=? FOR UPDATE'); $stmt->execute([$id]); $refund=$stmt->fetch();
            if (!$refund) throw new RuntimeException('Enregistrez d’abord une demande de remboursement.');
            if ($refund['status']==='completed') return;
            if ($refund['status']!=='requested' || $order['payment_status']!=='paid') throw new RuntimeException('Remboursement non disponible.');
            $this->db->prepare("UPDATE order_refunds SET status='completed',reference=?,method=?,admin_note=?,completed_at=NOW() WHERE order_id=?")->execute([$reference,$method,$note,$id]);
            $this->db->prepare("UPDATE orders SET payment_status='refunded',status='cancelled' WHERE id=?")->execute([$id]);
            $this->db->prepare("UPDATE payments SET status='refunded' WHERE order_id=? AND status='successful'")->execute([$id]);
            $this->revokeAccess($id,true);
            $this->history($id,'cancelled','Remboursement intégral exécuté : '.$reference.' / '.$method);
        });
    }
}
