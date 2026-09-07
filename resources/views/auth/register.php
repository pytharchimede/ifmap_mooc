<main class="customer-auth-shell">
    <a class="auth-logo customer-logo" href="/">
        <span><?php if(!empty($brand['logo'])): ?><img src="<?= htmlspecialchars($brand['logo']) ?>" alt="Logo <?= htmlspecialchars($brand['name']) ?>"><?php else: ?>IF<?php endif ?></span>
        <div><strong><?= htmlspecialchars($brand['name']) ?></strong><small>NOTRE FLAMBEAU</small></div>
    </a>
    <form class="auth-form customer-form" method="post" action="/inscription" data-register-form>
        <input type="hidden" name="course_id" value="<?= (int)($courseId??0) ?>">
        <p class="eyebrow">NOUVEAU COMPTE</p><h2>Rejoignez <?= htmlspecialchars($brand['name']) ?></h2>
        <p><?= !empty($courseId)?'Créez vos accès : votre inscription à la formation sera automatiquement rattachée à ce compte.':'Un seul compte pour vos formations, examens et attestations.' ?></p>
        <?php if($error): ?><div class="auth-error">! <?= htmlspecialchars($error) ?></div><?php endif ?>
        <label>Nom complet<input name="name" autocomplete="name" value="<?= htmlspecialchars($old['name']??'') ?>" required autofocus></label>
        <label>Adresse email<input type="email" name="email" autocomplete="email" value="<?= htmlspecialchars($old['email']??'') ?>" required></label>
        <label>Numéro de téléphone<input name="phone" autocomplete="tel" placeholder="+225 ..." value="<?= htmlspecialchars($old['phone']??'') ?>" required><small>Vous pourrez aussi utiliser ce numéro pour vous connecter.</small></label>
        <label>Type de compte<select name="role"><option value="learner">Apprenant</option><option value="instructor" <?= ($old['role']??'')==='instructor'?'selected':'' ?>>Formateur / enseignant</option></select></label>
        <label>Définir mon mot de passe<div class="password-field"><input type="password" name="password" minlength="8" autocomplete="new-password" required data-new-password><button type="button" data-password>Afficher</button></div><small>8 caractères minimum. Évitez votre nom et votre numéro.</small></label>
        <label>Confirmer le mot de passe<div class="password-field"><input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required><button type="button" data-password>Afficher</button></div></label>
        <label class="terms"><input type="checkbox" name="terms" value="1" required> J’accepte les conditions générales et la politique de confidentialité.</label>
        <button class="auth-submit">Créer mon compte et mes accès →</button>
        <p class="auth-switch">Déjà inscrit ? <a href="/connexion">Se connecter</a></p><a class="back-site" href="/">← Retour au site</a>
    </form>
</main>
