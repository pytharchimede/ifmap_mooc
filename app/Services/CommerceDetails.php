<?php
namespace App\Services;
use PDO;
final class CommerceDetails
{
    public static function payments(PDO $db, int $id): array
    {
        $stmt=$db->prepare('SELECT provider,provider_reference,transaction_reference,payer_phone,payment_channel,amount,status,paid_at FROM payments WHERE order_id=? ORDER BY id DESC');
        $stmt->execute([$id]); return $stmt->fetchAll();
    }
    public static function refund(PDO $db, int $id): ?array
    {
        $stmt=$db->prepare('SELECT * FROM order_refunds WHERE order_id=?');$stmt->execute([$id]);return $stmt->fetch() ?: null;
    }
    public static function label(string $status): string
    {
        return ['paid'=>'Déjà payé','pending'=>'En attente de paiement','cod'=>'À encaisser à la livraison','failed'=>'Paiement non confirmé','refunded'=>'Remboursé','unpaid'=>'Non payé','free'=>'Accès gratuit','successful'=>'Payé','initiated'=>'Paiement initié','requested'=>'Remboursement demandé','completed'=>'Remboursement exécuté','rejected'=>'Demande refusée'][$status] ?? $status;
    }
}
