<?php
use App\Services\CommerceDetails;
$escape=fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
$logistics=['pending'=>'À traiter','paid'=>'À préparer','processing'=>'En préparation','shipping'=>'En livraison','completed'=>'Livrée / terminée','cancelled'=>'Annulée'];
$counts=['active'=>0,'paid'=>0,'cancelled'=>0,'refund'=>0];
foreach($items as $item){if(!in_array($item['status'],['completed','cancelled'],true))$counts['active']++;if($item['payment_status']==='paid')$counts['paid']++;if($item['status']==='cancelled')$counts['cancelled']++;if(($item['refund']['status']??'')==='requested')$counts['refund']++;}
?>
<link rel="stylesheet" href="/public/assets/css/admin-orders.css">
<section class="orders-page" data-orders-page>
<header class="orders-heading"><div><p class="orders-eyebrow">GESTION COMMERCIALE</p><h1>Commandes</h1><p>Suivez vos ventes, organisez les livraisons et gérez les demandes clients.</p></div><a class="order-btn order-btn-outline" href="/admin/documents/export-commandes"><i data-icon="download"></i> Exporter</a></header>
<?php if(!empty($_SESSION['flash'])): ?><div class="orders-feedback" role="status"><?= $escape($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif ?>
<div class="orders-metrics">
<div><span>Commandes à traiter</span><strong><?= $counts['active'] ?></strong><small>Préparation et livraison</small></div>
<div><span>Commandes payées</span><strong><?= $counts['paid'] ?></strong><small>Encaissements confirmés</small></div>
<div><span>Remboursements demandés</span><strong><?= $counts['refund'] ?></strong><small>Dossiers à suivre</small></div>
<div><span>Commandes annulées</span><strong><?= $counts['cancelled'] ?></strong><small>Historique conservé</small></div>
</div>
<div class="orders-toolbar"><label class="orders-search"><i data-icon="search"></i><input type="search" data-order-search placeholder="Référence, client, téléphone, transaction…" aria-label="Rechercher une commande"></label><label class="orders-filter">Statut<select data-order-filter><option value="all">Toutes les commandes</option><option value="active">À traiter</option><option value="paid">Payées</option><option value="completed">Livrées / terminées</option><option value="cancelled">Annulées</option><option value="refund">Remboursement demandé</option></select></label><span data-order-count aria-live="polite"><?= count($items) ?> commande(s)</span></div>
<div class="orders-list">
<?php foreach($items as $order):
$closed=$order['status']==='cancelled';$paid=$order['payment_status']==='paid';$cod=$order['payment_status']==='cod';
$hasPhysical=(bool)($order['has_physical']??false);
$search=implode(' ',[$order['reference'],$order['customer']??'',$order['email']??'',$order['phone']??'',...array_map(fn($p)=>implode(' ',[$p['transaction_reference']??'',$p['provider_reference']??'',$p['payer_phone']??'']),$order['payments'])]);
$button=function(string $action,string $label,string $style='outline')use($order,$escape){ ?><button type="button" class="order-btn order-btn-<?= $escape($style) ?>" data-order-action="<?= $escape($action) ?>" data-order-id="<?= (int)$order['id'] ?>" data-order-reference="<?= $escape($order['reference']) ?>"><?= $escape($label) ?></button><?php };
?>
<article class="order-card <?= $closed?'is-cancelled':'' ?>" data-order-card data-search="<?= $escape($search) ?>" data-status="<?= $escape($order['status']) ?>" data-paid="<?= $paid?'1':'0' ?>" data-refund="<?= ($order['refund']['status']??'')==='requested'?'1':'0' ?>">
<header class="order-card-header"><div class="order-identity"><span class="order-symbol" aria-hidden="true"><i data-icon="<?= $hasPhysical?'box':'book' ?>"></i></span><div><h2><?= $escape($order['reference']) ?></h2><p><?= $escape(date('d/m/Y à H:i',strtotime($order['created_at']))) ?> · <?= $hasPhysical?'Commande boutique':'Formation / numérique' ?></p></div></div><div class="order-badges"><span class="order-badge logistics-<?= $escape($order['status']) ?>"><?= $escape($logistics[$order['status']]??$order['status']) ?></span><span class="order-badge payment-<?= $escape($order['payment_status']) ?>"><?= $escape(CommerceDetails::label($order['payment_status'])) ?></span></div></header>
<div class="order-overview"><div><span class="order-caption">CLIENT</span><strong><?= $escape($order['customer']?:'Client') ?></strong><span><?= $escape($order['email']) ?></span><?php if($order['phone']): ?><span><?= $escape($order['phone']) ?></span><?php endif ?></div><div><span class="order-caption">SUIVI</span><strong><?= $hasPhysical?($order['delivery_method']==='pickup'?'Retrait sur place':'Livraison à domicile'):'Accès dans l’espace client' ?></strong><?php if($order['delivered_at']): ?><span>Terminé le <?= $escape(date('d/m/Y',strtotime($order['delivered_at']))) ?></span><?php endif ?><?php if($order['returned_at']): ?><span>Retour reçu le <?= $escape(date('d/m/Y',strtotime($order['returned_at']))) ?></span><?php endif ?><?php if(!empty($order['refund'])): ?><span><?= $escape(CommerceDetails::label($order['refund']['status'])) ?></span><?php endif ?></div><div class="order-amount"><span class="order-caption">TOTAL COMMANDE</span><strong><?= number_format((float)$order['total'],0,',',' ') ?> <small>FCFA</small></strong></div></div>
<div class="order-actions"><div class="order-primary-actions">
<?php if(!$closed&&!$order['returned_at']): ?>
<?php if($cod)$button('collect_cod','Enregistrer l’encaissement','primary'); ?>
<?php if(in_array($order['status'],['pending','paid'],true)&&($paid||$cod))$button('processing','Préparer la commande','primary'); ?>
<?php if($hasPhysical&&$order['status']==='processing'&&($paid||$cod))$button('shipping','Mettre en livraison','primary'); ?>
<?php if(in_array($order['status'],['paid','processing','shipping'],true)&&$paid)$button('completed',$hasPhysical?'Confirmer la remise':'Terminer la commande','primary'); ?>
<?php endif ?>
<?php if($paid&&(float)$order['total']>0&&empty($order['refund']))$button('request_refund','Demander un remboursement'); ?>
<?php if(($order['refund']['status']??'')==='requested'&&$paid)$button('complete_refund','Confirmer le remboursement'); ?>
<?php if($hasPhysical&&!$order['returned_at']&&(int)($order['stock_out']??0)>0)$button('receive_return','Enregistrer un retour'); ?>
</div><?php if(!$closed): $button('cancelled','Annuler la commande','danger'); else: ?><span class="order-closed-label">Commande clôturée par annulation</span><?php endif ?></div>
<div class="order-details-grid"><details class="order-disclosure"><summary><i data-icon="wallet"></i> Paiement et justificatifs</summary><div><?php require __DIR__.'/../partials/payment-details.php'; ?><div class="order-documents"><a class="order-btn order-btn-outline" href="/admin/documents/commande?id=<?= (int)$order['id'] ?>" target="_blank" rel="noopener">Bon de commande ↗</a><?php if($hasPhysical): ?><a class="order-btn order-btn-outline" href="/admin/documents/livraison?id=<?= (int)$order['id'] ?>" target="_blank" rel="noopener">Bon de livraison ↗</a><?php endif ?></div></div></details><details class="order-disclosure"><summary><i data-icon="clock"></i> Historique <span><?= count($order['history']) ?></span></summary><ol class="order-timeline"><?php foreach($order['history'] as $event): ?><li><time><?= $escape($event['created_at']) ?></time><strong><?= $escape($logistics[$event['status']]??$event['status']) ?></strong><p><?= $escape($event['note']??'') ?></p></li><?php endforeach ?><?php if(!$order['history']): ?><li>Aucun événement enregistré.</li><?php endif ?></ol></details></div>
</article>
<?php endforeach ?>
</div>
<div class="orders-empty" data-order-empty <?= $items?'hidden':'' ?>><i data-icon="search"></i><h2><?= $items?'Aucune commande trouvée':'Aucune commande pour le moment' ?></h2><p>Les commandes de la boutique et des formations apparaissent ici.</p><button class="order-btn order-btn-outline" type="button" data-order-reset>Réinitialiser les filtres</button></div>
<dialog class="order-dialog" data-order-dialog aria-labelledby="order-dialog-title" aria-describedby="order-dialog-description">
<form method="post" data-order-form data-status-url="/admin/commandes/statut" data-action-url="/admin/commandes/action" action="/admin/commandes/statut">
<input type="hidden" name="id"><input type="hidden" name="csrf" value="<?= $escape($_SESSION['commerce_csrf']) ?>"><input type="hidden" name="status"><input type="hidden" name="action">
<div class="order-dialog-heading"><div><p class="orders-eyebrow" data-dialog-reference></p><h2 id="order-dialog-title"></h2></div><button class="order-dialog-close" type="button" data-dialog-close aria-label="Fermer">×</button></div>
<p id="order-dialog-description" class="order-dialog-description"></p>
<label class="order-field"> <span data-note-label>Note de suivi</span><textarea name="note" rows="3" maxlength="255"></textarea></label>
<div data-financial-fields hidden><label class="order-field">Référence du reçu ou du remboursement<input name="reference" maxlength="190"></label><label class="order-field">Moyen utilisé<input name="method" maxlength="80" placeholder="Mobile Money, espèces, virement…"></label></div>
<label class="order-field" data-phone-field hidden>Téléphone du payeur (facultatif)<input name="payer_phone" type="tel" maxlength="40"></label>
<label class="order-field" data-return-field hidden>État des articles<select data-return-type><option value="receive_return">Contrôlés et aptes à la remise en stock</option><option value="receive_return_damaged">Endommagés / à contrôler — sans remise en stock</option></select></label>
<footer class="order-dialog-footer"><button type="button" class="order-btn order-btn-outline" data-dialog-close>Fermer</button><button class="order-btn order-btn-primary" type="submit" data-dialog-submit>Confirmer</button></footer>
</form></dialog>
</section>
<script src="/public/assets/js/admin-orders.js" defer></script>
