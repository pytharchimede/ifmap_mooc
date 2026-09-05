<script src="https://cdn.cinetpay.com/seamless/main.js"></script>
<section class="payment-gateway"><div class="gateway-card"><div class="gateway-lock">⌁</div><p class="site-kicker">PAIEMENT MOBILE SÉCURISÉ</p><h1>Finalisez votre commande</h1><p>Le guichet CinetPay va s’ouvrir directement dans votre navigateur.</p><div class="gateway-order"><span>Commande <b><?= htmlspecialchars($order['reference']) ?></b></span><strong><?= number_format((float)$order['total'],0,',',' ') ?> FCFA</strong></div><div class="gateway-items"><?php foreach($items as $item): ?><span><?= (int)$item['quantity'] ?> × <?= htmlspecialchars($item['label']) ?></span><?php endforeach ?></div><button class="site-btn gold wide" type="button" data-open-cinetpay>Ouvrir le guichet CinetPay →</button><a class="gateway-back" href="/commande">← Revenir à ma commande</a><small data-payment-state>Votre panier reste conservé tant que le paiement n’est pas confirmé.</small></div></section>
<script>
window.addEventListener('DOMContentLoaded',function(){
 const button=document.querySelector('[data-open-cinetpay]'),state=document.querySelector('[data-payment-state]'),data=<?= json_encode($checkoutData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
 function openCheckout(){
  if(typeof CinetPay==='undefined'){state.textContent='Le guichet ne peut pas être chargé. Vérifiez votre connexion puis réessayez.';return;}
  button.disabled=true;button.textContent='Ouverture du guichet…';
  CinetPay.setConfig({apikey:data.apikey,site_id:data.site_id,notify_url:data.notify_url,mode:data.mode});
  CinetPay.getCheckout(data);
  CinetPay.waitResponse(function(response){
   if(response.status==='ACCEPTED'){state.textContent='Paiement accepté. Vérification en cours…';location.href='/paiement/cinetpay/retour?transaction_id='+encodeURIComponent(data.transaction_id);}
   else{button.disabled=false;button.textContent='Réessayer le paiement';state.textContent='Le paiement n’a pas été accepté. Votre panier est toujours conservé.';}
  });
  CinetPay.onError(function(){button.disabled=false;button.textContent='Réessayer le paiement';state.textContent='Le guichet a rencontré une erreur. Votre panier est conservé.';});
 }
 button.addEventListener('click',openCheckout);openCheckout();
});
</script>
<style>.payment-gateway{min-height:65vh;padding:60px 20px;display:grid;place-items:center}.gateway-card{width:min(100%,560px);padding:38px;border:1px solid #dce4df;border-radius:24px;background:#fff;text-align:center;box-shadow:0 24px 60px rgba(18,63,58,.12)}.gateway-lock{width:58px;height:58px;margin:0 auto 18px;border-radius:18px;display:grid;place-items:center;background:#e9f3ef;color:#123f3a;font-size:27px}.gateway-card h1{font:800 30px Manrope;margin:6px 0 10px}.gateway-card>p:not(.site-kicker){color:#687a76}.gateway-order{display:flex;align-items:center;justify-content:space-between;margin:25px 0 0;padding:17px;border-radius:13px;background:#f3f6f4}.gateway-order strong{font-size:18px;color:#123f3a}.gateway-items{display:flex;flex-direction:column;align-items:flex-start;gap:6px;padding:14px 17px;margin-bottom:15px;border-bottom:1px solid #e4e9e6;color:#687a76;font-size:10px}.gateway-back{display:block;margin:16px;color:#526762;font-size:10px}.gateway-card>small{display:block;color:#74847f;line-height:1.5}@media(max-width:550px){.payment-gateway{padding:25px 14px}.gateway-card{padding:27px 18px}.gateway-order{align-items:flex-start;flex-direction:column;gap:5px}}</style>
