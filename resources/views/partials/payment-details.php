<?php use App\Services\CommerceDetails; ?>
<div class="payment-details">
<strong><?= htmlspecialchars(CommerceDetails::label((string)($order['payment_status']??'pending'))) ?></strong>
<p>Commande : <b><?= htmlspecialchars($order['reference']) ?></b> · <?= number_format((float)$order['total'],0,',',' ') ?> FCFA</p>
<?php $contact=$order['phone']??(json_decode($order['customer_data']??'{}',true)['phone']??''); ?>
<?php if($contact): ?><p>Téléphone de contact : <?= htmlspecialchars($contact) ?></p><?php endif ?>
<?php foreach($order['payments']??[] as $payment): ?>
<div><p><?= htmlspecialchars($payment['provider']) ?> · <?= htmlspecialchars(CommerceDetails::label($payment['status'])) ?> · <?= number_format((float)$payment['amount'],0,',',' ') ?> FCFA<?php if($payment['paid_at']): ?> · <?= htmlspecialchars($payment['paid_at']) ?><?php endif ?></p>
<p>Référence de transaction : <b><?= htmlspecialchars($payment['transaction_reference']?:'Non communiquée par l’opérateur') ?></b></p>
<?php if($payment['provider_reference']): ?><p>Référence de session/prestataire : <?= htmlspecialchars($payment['provider_reference']) ?></p><?php endif ?>
<p>Téléphone du payeur communiqué à l’encaissement : <?= htmlspecialchars($payment['payer_phone']?:'Non communiqué') ?><?php if($payment['payment_channel']): ?> · Moyen : <?= htmlspecialchars($payment['payment_channel']) ?><?php endif ?></p></div>
<?php endforeach ?>
<?php if(!empty($order['refund'])): $refund=$order['refund']; ?><p><b><?= htmlspecialchars(CommerceDetails::label($refund['status'])) ?></b> · <?= number_format((float)$refund['amount'],0,',',' ') ?> FCFA<?php if($refund['reference']): ?> · Référence <?= htmlspecialchars($refund['reference']) ?> · <?= htmlspecialchars($refund['method']) ?> · <?= htmlspecialchars($refund['completed_at']) ?><?php endif ?></p><?php endif ?>
</div>
<style>.payment-details{padding:16px;border:1px solid #dce7e1;border-radius:10px;margin:14px 0;text-align:left;overflow-wrap:anywhere}.payment-details>strong{color:#145c41}.payment-details p{font-size:12px;margin:7px 0}.payment-details>div{border-top:1px solid #dce7e1;margin-top:12px;padding-top:5px}</style>
