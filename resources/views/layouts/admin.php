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
        <div class="page admin-page"><?php if($flash): ?><div class="toast">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?><?= $content ?></div>
    </main>
</div>
<script src="/public/assets/js/app.js"></script>
</body>
</html>
