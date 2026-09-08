<main class="customer-auth-shell auth-comms-shell">
<a class="auth-logo customer-logo" href="/"><span><?php if(!empty($brand['logo'])): ?><img src="<?= htmlspecialchars($brand['logo']) ?>" alt="Logo <?= htmlspecialchars($brand['name']) ?>"><?php else: ?>IF<?php endif ?></span><div><strong><?= htmlspecialchars($brand['name']) ?></strong><small>RÉCUPÉRATION DE COMPTE</small></div></a>
<form class="auth-form customer-form auth-comms-card" method="post" action="/mot-de-passe-oublie">
<p class="eyebrow">ACCÈS SÉCURISÉ</p><h2>Retrouver votre compte</h2><p>Indiquez votre email ou votre numéro puis choisissez comment recevoir votre lien sécurisé de réinitialisation.</p>
<?php if($flash): ?><div class="toast">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?><?php if($error): ?><div class="auth-error">! <?= htmlspecialchars($error) ?></div><?php endif ?>
<label>Email ou numéro de téléphone<input name="identifier" value="<?= htmlspecialchars($old['identifier']??'') ?>" placeholder="email@exemple.ci ou +225..." required autofocus></label>
<div class="channel-choice"><span>Recevoir le lien par</span><?php foreach(['email'=>'Email','sms'=>'SMS','whatsapp'=>'WhatsApp'] as $key=>$label): $enabled=in_array($key,$channels??[],true)&&!empty($configured[$key]); ?><label class="channel-card <?= $enabled?'':'disabled' ?>"><input type="radio" name="channel" value="<?= $key ?>" <?= (($old['channel']??'email')===$key)?'checked':'' ?> <?= $enabled?'':'disabled' ?>><b><?= $label ?></b><small><?= $enabled?'Canal disponible':'Configuration en attente' ?></small></label><?php endforeach ?></div>
<button class="auth-submit">Envoyer mon lien sécurisé →</button><p class="auth-switch"><a href="/connexion">← Retour à la connexion</a></p>
</form></main>
