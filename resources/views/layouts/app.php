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
<p><?= ($_SESSION['user']['role'] ?? 'learner') === 'instructor' ? 'ESPACE FORMATEUR' : 'ESPACE APPRENANT' ?></p>
<a class="<?= $active==='dashboard'?'active':'' ?>" href="/academie"><i data-icon="grid"></i>Tableau de bord</a>
<a class="<?= $active==='courses'?'active':'' ?>" href="/cours"><i data-icon="book"></i>Mes formations <b>3</b></a>
<a class="<?= $active==='catalog'?'active':'' ?>" href="/catalogue"><i data-icon="compass"></i>Catalogue</a>
<a href="#"><i data-icon="award"></i>Mes certificats</a>
<p>MON COMPTE</p><a href="/academie#profil"><i data-icon="user"></i>Profil et préférences</a>
</nav><div class="sidebar-help"><span>?</span><div><strong>Besoin d’aide ?</strong><small>Notre équipe vous répond</small></div></div>
<form method="post" action="/deconnexion"><button class="admin-link logout-link" type="submit"><i data-icon="user"></i>Se déconnecter</button></form>
<?php if(($_SESSION['user']['role']??'')==='admin'): ?><a class="admin-link" href="/admin"><i data-icon="shield"></i>Administration</a><?php endif ?></aside>
<main class="main"><header class="topbar"><button class="icon-btn mobile-menu" data-menu>☰</button><label class="search"><i data-icon="search"></i><input placeholder="Rechercher une formation..."><kbd>⌘ K</kbd></label><div class="top-actions"><button class="icon-btn dot"><i data-icon="bell"></i></button><a class="profile" href="/academie#profil"><span class="avatar"><?php if(!empty($_SESSION['user']['avatar'])): ?><img src="<?= htmlspecialchars($_SESSION['user']['avatar']) ?>" alt="Photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"><?php else: ?><?= htmlspecialchars(strtoupper(substr($_SESSION['user']['name']??'U',0,1))) ?><?php endif ?></span><div><strong><?= htmlspecialchars($_SESSION['user']['name']??'Utilisateur') ?></strong><small><?= ($_SESSION['user']['role']??'learner')==='instructor'?'Formateur':(($_SESSION['user']['role']??'learner')==='admin'?'Administrateur':'Apprenant') ?></small></div><span>⌄</span></a></div></header>
<div class="page"><?= $content ?></div></main></div>
<script src="/public/assets/js/app.js"></script></body></html>
