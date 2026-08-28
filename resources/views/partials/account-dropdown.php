<?php
$dropdownUser=$_SESSION['user']??null;
$dropdownAdmin=!empty($_SESSION['admin_authenticated']);
$dropdownContext=$accountContext??'site';
if($dropdownUser):
    $dropdownRole=($dropdownUser['role']??'learner')==='admin'?'Administrateur':(($dropdownUser['role']??'learner')==='instructor'?'Formateur':'Apprenant');
    $dropdownName=$dropdownUser['name']??($_SESSION['admin_name']??'Utilisateur IFMAP');
    $dropdownInitials=strtoupper(substr($dropdownName,0,1).(str_contains($dropdownName,' ')?substr(strrchr($dropdownName,' '),1,1):''));
?>
<div class="account-dropdown" data-account-dropdown>
    <button class="account-trigger" type="button" data-account-toggle aria-expanded="false" aria-haspopup="true">
        <span class="account-trigger-avatar"><?php if(!empty($dropdownUser['avatar'])): ?><img src="<?= htmlspecialchars($dropdownUser['avatar']) ?>" alt="Photo de profil"><?php else: ?><?= htmlspecialchars($dropdownInitials) ?><?php endif ?></span>
        <span class="account-trigger-copy"><strong><?= htmlspecialchars($dropdownName) ?></strong><small><?= $dropdownRole ?> · Connecté</small></span>
        <span class="account-chevron">⌄</span>
    </button>
    <div class="account-menu" data-account-menu hidden>
        <div class="account-menu-head"><span class="account-trigger-avatar"><?php if(!empty($dropdownUser['avatar'])): ?><img src="<?= htmlspecialchars($dropdownUser['avatar']) ?>" alt=""><?php else: ?><?= htmlspecialchars($dropdownInitials) ?><?php endif ?></span><div><strong><?= htmlspecialchars($dropdownName) ?></strong><small><?= htmlspecialchars($dropdownUser['email']??$dropdownRole) ?></small></div></div>
        <div class="account-online"><i></i> Vous êtes connecté comme <?= strtolower($dropdownRole) ?></div>
        <?php if($dropdownAdmin): ?><a href="/admin"><span>▦</span><div><strong>Administration</strong><small>Piloter la plateforme</small></div></a><?php else: ?><a href="/academie"><span>▦</span><div><strong>Mon tableau de bord</strong><small>Formations et progression</small></div></a><?php endif ?>
        <a href="/academie#profil"><span>◯</span><div><strong>Gérer mon profil</strong><small>Photo, CV et informations</small></div></a>
        <a href="/academie#profil"><span>⌾</span><div><strong>Mot de passe</strong><small>Sécurité du compte</small></div></a>
        <form method="post" action="<?= $dropdownAdmin?'/admin/deconnexion':'/deconnexion' ?>"><button type="submit"><span>↪</span><div><strong>Se déconnecter</strong><small>Fermer cette session</small></div></button></form>
    </div>
</div>
<?php elseif($dropdownContext==='site'): ?>
<a class="account-login-link" href="/connexion"><span>◯</span> Se connecter</a>
<?php endif ?>
