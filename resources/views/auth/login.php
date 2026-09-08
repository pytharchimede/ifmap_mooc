<main class="customer-auth-shell">
    <a class="auth-logo customer-logo" href="/">
        <span>
            <?php if(!empty($brand['logo'])): ?>
                <img src="<?= htmlspecialchars($brand['logo']) ?>" alt="Logo <?= htmlspecialchars($brand['name']) ?>">
            <?php else: ?>
                IF
            <?php endif ?>
        </span>
        <div>
            <strong><?= htmlspecialchars($brand['name']) ?></strong>
            <small>NOTRE FLAMBEAU</small>
        </div>
    </a>

    <form class="auth-form customer-form" method="post" action="/connexion">
        <p class="eyebrow">ESPACE APPRENANT, FORMATEUR & MENTOR</p>
        <h2>Heureux de vous revoir</h2>
        <p>Retrouvez vos formations, examens, mentorat et documents.</p>

        <?php if($flash): ?><div class="toast">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?>
        <?php if($error): ?><div class="auth-error">! <?= htmlspecialchars($error) ?></div><?php endif ?>

        <label>Email ou numéro de téléphone
            <input name="identifier" autocomplete="username" placeholder="email@exemple.ci ou +225..." required autofocus>
        </label>

        <label>Mot de passe
            <div class="password-field">
                <input type="password" name="password" autocomplete="current-password" required>
                <button type="button" data-password>Afficher</button>
            </div>
        </label>
        <div class="auth-recovery-row"><span></span><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></div>

        <button class="auth-submit">Accéder à mon espace →</button>
        <p class="auth-switch">Pas encore de compte ? <a href="/inscription">Créer mon compte</a></p>
        <a class="back-site" href="/">← Retour au site</a>
    </form>
</main>
