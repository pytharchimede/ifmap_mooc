<?php $brand = $_SESSION['brand'] ?? ['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b']; $active = $active ?? ''; ?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title ?? '') ?> — <?= htmlspecialchars($brand['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/public/assets/css/app.css">
<style>:root{--primary:<?= htmlspecialchars($brand['primary']) ?>;--accent:<?= htmlspecialchars($brand['accent']) ?>}</style></head>
<body><div class="app-shell">
<aside class="sidebar" id="sidebar"><div class="sidebar-head"><?php require __DIR__.'/../partials/logo.php'; ?><button class="icon-btn close-mobile" data-menu>×</button></div>
<nav class="main-nav">
<p>ESPACE APPRENANT</p>
<a class="<?= $active==='dashboard'?'active':'' ?>" href="/"><i data-icon="grid"></i>Tableau de bord</a>
<a class="<?= $active==='courses'?'active':'' ?>" href="/cours"><i data-icon="book"></i>Mes formations <b>3</b></a>
<a class="<?= $active==='catalog'?'active':'' ?>" href="/catalogue"><i data-icon="compass"></i>Catalogue</a>
<a href="#"><i data-icon="award"></i>Mes certificats</a>
<p>MON COMPTE</p><a href="/profil"><i data-icon="user"></i>Mon profil</a><a href="#"><i data-icon="settings"></i>Préférences</a>
</nav><div class="sidebar-help"><span>?</span><div><strong>Besoin d’aide ?</strong><small>Notre équipe vous répond</small></div></div>
<a class="admin-link" href="/admin"><i data-icon="shield"></i>Administration</a></aside>
<main class="main"><header class="topbar"><button class="icon-btn mobile-menu" data-menu>☰</button><label class="search"><i data-icon="search"></i><input placeholder="Rechercher une formation..."><kbd>⌘ K</kbd></label><div class="top-actions"><button class="icon-btn dot"><i data-icon="bell"></i></button><div class="profile"><span class="avatar">AK</span><div><strong>Assa Kouamé</strong><small>Apprenante</small></div><span>⌄</span></div></div></header>
<div class="page"><?= $content ?></div></main></div>
<script src="/public/assets/js/app.js"></script></body></html>
