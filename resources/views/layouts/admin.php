<?php
$brand=$_SESSION['brand']??['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b'];
$flash=$_SESSION['flash']??null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Administration IFMAP</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/app.css">
    <link rel="stylesheet" href="/public/assets/css/admin-forms.css">
    <style>:root{--primary:<?= htmlspecialchars($brand['primary']) ?>;--accent:<?= htmlspecialchars($brand['accent']) ?>}</style>
</head>
<body class="admin-body">
<div class="app-shell">
    <aside class="sidebar admin-sidebar" id="sidebar">
        <div class="sidebar-head"><?php require __DIR__.'/../partials/logo.php'; ?><button class="icon-btn close-mobile" data-menu>×</button></div>
        <span class="admin-badge">ADMINISTRATION</span>
        <?php require __DIR__.'/../partials/admin-navigation.php'; ?>
        <form class="admin-user" method="post" action="/admin/deconnexion">
            <span class="avatar">AD</span>
            <div><strong><?= htmlspecialchars($_SESSION['admin_name']??'Administrateur') ?></strong><small>Super administrateur</small></div>
            <button class="icon-btn" title="Se déconnecter" aria-label="Se déconnecter">↪</button>
        </form>
    </aside>
    <main class="main">
        <header class="topbar admin-top">
            <button class="icon-btn mobile-menu" data-menu>☰</button>
            <div><h2><?= htmlspecialchars($title) ?></h2><p>Console d’administration IFMAP</p></div>
            <div class="top-actions"><a class="btn secondary" href="/">↗ Voir la plateforme</a><a class="icon-btn dot" href="/admin/commandes"><i data-icon="bell"></i></a><?php $accountContext='admin';require __DIR__.'/../partials/account-dropdown.php'; ?></div>
        </header>
        <div class="page admin-page"><?php if($flash): ?><div class="toast">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?><?= $content ?>
        <?php if(($active??'')==='admin-branding'): ?><form class="panel branding-signature-form" method="post" action="/admin/branding" enctype="multipart/form-data"><input type="hidden" name="name" value="<?= htmlspecialchars($brand['name']) ?>"><input type="hidden" name="primary" value="<?= htmlspecialchars($brand['primary']) ?>"><input type="hidden" name="accent" value="<?= htmlspecialchars($brand['accent']) ?>"><div class="brand-logo-preview"><?php if(!empty($brand['signature'])): ?><img src="<?= htmlspecialchars($brand['signature']) ?>" alt="Signature actuelle"><?php else: ?><b>✍</b><?php endif ?></div><div><h3>Signature numérique du diplôme</h3><p>JPG, JPEG ou PNG transparent · 2 Mo maximum. La signature sera apposée sur les diplômes officiels.</p><input type="file" name="signature" accept="image/jpeg,image/png" required></div><button class="btn primary">Enregistrer la signature</button></form><?php endif ?>
        </div>
    </main>
</div>
<script src="/public/assets/js/app.js"></script>
<style>.branding-signature-form{margin-top:20px;padding:22px;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:18px}.branding-signature-form .brand-logo-preview{width:110px;height:75px;background:#f4f5f7;border:1px dashed #ccd0da;border-radius:12px;display:grid;place-items:center;overflow:hidden}.branding-signature-form img{width:100%;height:100%;object-fit:contain}.branding-signature-form h3{margin:0 0 5px}.branding-signature-form p{margin:0 0 10px;color:#747b89;font-size:11px}@media(max-width:760px){.branding-signature-form{grid-template-columns:1fr}.branding-signature-form .brand-logo-preview{width:100%}}</style>
</body>
</html>
