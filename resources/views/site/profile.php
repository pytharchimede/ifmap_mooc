<?php
$initials = strtoupper(substr($user['name'], 0, 1) . (str_contains($user['name'], ' ') ? substr(strrchr($user['name'], ' '), 1, 1) : ''));
$isInstructor = ($user['role'] ?? 'learner') === 'instructor';
?>
<section class="profile-page">
    <div class="profile-heading">
        <div>
            <p class="site-kicker">PARAMÈTRES DU COMPTE</p>
            <h1>Mon profil</h1>
            <p>Gérez vos informations personnelles et les informations visibles sur vos fiches de formation.</p>
        </div>
        <a class="site-btn outline" href="/mon-compte">← Mon espace</a>
    </div>

    <?php if ($flash): ?><div class="site-flash">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?>
    <?php if ($error): ?><div class="auth-error">! <?= htmlspecialchars($error) ?></div><?php endif ?>

    <div class="profile-grid">
        <form class="panel profile-card" method="post" action="/profil" enctype="multipart/form-data">
            <header>
                <h2>Informations personnelles</h2>
                <p>Ces informations peuvent apparaître sur vos attestations et fiches de formation.</p>
            </header>
            <div class="avatar-editor">
                <div class="profile-photo">
                    <?php if (!empty($user['avatar'])): ?>
                        <img id="avatar-preview" src="<?= htmlspecialchars($user['avatar']) ?>" alt="Photo de profil">
                    <?php else: ?>
                        <span id="avatar-initials"><?= htmlspecialchars($initials) ?></span>
                        <img id="avatar-preview" hidden alt="Aperçu">
                    <?php endif ?>
                </div>
                <label class="site-btn outline">Changer la photo
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" data-avatar-input hidden>
                </label>
                <small>JPG, PNG ou WebP · 5 Mo maximum</small>
            </div>
            <div class="field-grid">
                <label>Nom complet
                    <input name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </label>
                <label>Adresse email
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    <small>L’adresse de connexion ne peut pas être modifiée ici.</small>
                </label>
                <label>Téléphone
                    <input name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+225 ...">
                </label>
                <label>Type de compte
                    <input value="<?= $isInstructor ? 'Formateur' : (($user['role'] ?? 'learner') === 'admin' ? 'Administrateur' : 'Apprenant') ?>" disabled>
                </label>
            </div>

            <?php if ($isInstructor): ?>
                <div class="instructor-profile-fields">
                    <h3>Présentation formateur</h3>
                    <label>Spécialité
                        <input name="specialty" value="<?= htmlspecialchars($user['specialty'] ?? '') ?>" placeholder="Ex. Gestion des stations-service">
                    </label>
                    <label>Biographie professionnelle
                        <textarea name="bio" rows="5" placeholder="Présentez votre expérience et votre expertise."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </label>
                    <label>CV
                        <input type="file" name="cv" accept="application/pdf,.doc,.docx">
                        <small>PDF, DOC ou DOCX · 10 Mo maximum</small>
                        <?php if (!empty($user['cv_path'])): ?><a class="profile-file" href="<?= htmlspecialchars($user['cv_path']) ?>" target="_blank" rel="noopener">Voir le CV actuel</a><?php endif ?>
                    </label>
                </div>
            <?php endif ?>

            <footer><button class="site-btn green">Enregistrer les modifications</button></footer>
        </form>

        <form class="panel profile-card security-card" method="post" action="/profil/mot-de-passe">
            <header><h2>Sécurité</h2><p>Modifiez votre mot de passe régulièrement.</p></header>
            <label>Mot de passe actuel<input type="password" name="current_password" required></label>
            <label>Nouveau mot de passe<input type="password" name="password" minlength="8" required></label>
            <label>Confirmation<input type="password" name="password_confirmation" minlength="8" required></label>
            <footer><button class="site-btn outline">Modifier le mot de passe</button></footer>
        </form>
    </div>
</section>

<style>
    .instructor-profile-fields { margin-top: 28px; padding-top: 24px; border-top: 1px solid #e1e7e4; }
    .instructor-profile-fields h3 { margin-bottom: 16px; }
    .instructor-profile-fields label { display: flex; flex-direction: column; gap: 7px; margin-bottom: 16px; }
    .instructor-profile-fields textarea { border: 1px solid #d5dfdb; border-radius: 6px; padding: 11px; resize: vertical; font: inherit; }
    .profile-file { display: inline-block; margin-top: 8px; color: #176b48; font-weight: 700; font-size: 12px; }
</style>
