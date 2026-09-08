<main class="customer-auth-shell auth-comms-shell">
    <a class="auth-logo customer-logo" href="/">
        <span><?php if(!empty($brand['logo'])): ?><img src="<?= htmlspecialchars($brand['logo']) ?>" alt="Logo <?= htmlspecialchars($brand['name']) ?>"><?php else: ?>IF<?php endif ?></span>
        <div><strong><?= htmlspecialchars($brand['name']) ?></strong><small>ACTIVATION</small></div>
    </a>
    <div class="auth-form customer-form auth-comms-card">
        <p class="eyebrow">VÉRIFICATION DU COMPTE</p>
        <h2>Activez votre espace</h2>
        <?php if(($otpMode??'display')==='display'): ?>
            <p>IFMAP utilise actuellement le mode d’activation simplifié : votre code OTP est affiché directement ci-dessous et reste valable 15 minutes.</p>
        <?php else: ?>
            <p>Entrez le code à 6 chiffres envoyé par <strong><?= htmlspecialchars(['email'=>'email','sms'=>'SMS','whatsapp'=>'WhatsApp'][$otpChannel??'email']??($otpChannel??'notification')) ?></strong>. Il expire après 15 minutes.</p>
        <?php endif ?>
        <?php if($flash): ?><div class="toast">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?>
        <?php if($error): ?><div class="auth-error">! <?= htmlspecialchars($error) ?></div><?php endif ?>
        <?php if($demoOtp): ?><div class="demo-credentials otp-display-card"><span>Code OTP IFMAP</span><code><?= htmlspecialchars($demoOtp) ?></code><small>Ce code est affiché parce que le mode « affichage direct » est actif dans les paramètres administrateur.</small></div><?php endif ?>
        <form method="post" action="/activation"><label>Code OTP<input name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus></label><button class="auth-submit">Activer mon compte →</button></form>
        <form method="post" action="/activation/renvoyer"><button class="auth-secondary" type="submit"><?= ($otpMode??'display')==='display'?'Générer un nouveau code':'Je n’ai pas reçu le code · Renvoyer' ?></button></form>
        <p class="auth-switch">Besoin d’aide ? L’administrateur peut également activer manuellement votre compte.</p>
    </div>
</main>
