<?php $brand = $_SESSION['brand'] ?? ['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b']; $active = $active ?? ''; $role=$_SESSION['user']['role'] ?? 'learner'; ?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title ?? '') ?> — <?= htmlspecialchars($brand['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/public/assets/css/app.css">
<link rel="stylesheet" href="/public/assets/css/testimonials.css?v=1">
<style>:root{--primary:<?= htmlspecialchars($brand['primary']) ?>;--accent:<?= htmlspecialchars($brand['accent']) ?>;--site-primary:<?= htmlspecialchars($brand['primary']) ?>;--site-accent:<?= htmlspecialchars($brand['accent']) ?>}</style></head>
<body><div class="app-shell">
<aside class="sidebar" id="sidebar"><div class="sidebar-head"><?php require __DIR__.'/../partials/logo.php'; ?><button class="icon-btn close-mobile" data-menu>×</button></div>
<nav class="main-nav">
<p><?= $role==='mentor'?'ESPACE MENTOR':($role==='instructor'?'ESPACE FORMATEUR':'ESPACE APPRENANT') ?></p>
<a class="<?= $active==='dashboard'?'active':'' ?>" href="/academie"><i data-icon="grid"></i>Tableau de bord</a>
<?php if($role==='mentor'): ?><a class="<?= $active==='mentor'?'active':'' ?>" href="/mentor"><i data-icon="users"></i>Mon espace mentor</a><?php endif ?>
<a class="<?= $active==='courses'?'active':'' ?>" href="/cours"><i data-icon="book"></i>Mes formations<?php if(!empty($_SESSION['my_course_count'])): ?><b><?= (int)$_SESSION['my_course_count'] ?></b><?php endif ?></a>
<a class="<?= $active==='catalog'?'active':'' ?>" href="/catalogue"><i data-icon="compass"></i>Catalogue</a>
<a class="<?= $active==='mentorship'?'active':'' ?>" href="/academie/mentorat"><i data-icon="users"></i>Mentorat & coaching</a>
<a class="<?= $active==='certificates'?'active':'' ?>" href="/academie/certificats"><i data-icon="award"></i>Mes certificats</a>
<a class="<?= $active==='testimonial'?'active':'' ?>" href="/academie/temoignage"><i data-icon="play"></i>Mon témoignage vidéo</a>
<p>MON COMPTE</p><a href="/academie#profil"><i data-icon="user"></i>Profil et préférences</a><?php if($role!=='mentor'): ?><a href="/devenir-mentor"><i data-icon="award"></i>Devenir mentor</a><?php endif ?>
</nav><div class="sidebar-help"><span>?</span><div><strong>Besoin d’aide ?</strong><small>Notre équipe vous répond</small></div></div>
<form method="post" action="/deconnexion"><button class="admin-link logout-link" type="submit"><i data-icon="user"></i>Se déconnecter</button></form>
<?php if($role==='admin'): ?><a class="admin-link" href="/admin"><i data-icon="shield"></i>Administration</a><?php endif ?></aside>
<main class="main"><header class="topbar"><button class="icon-btn mobile-menu" data-menu>☰</button><label class="search"><i data-icon="search"></i><input placeholder="Rechercher une formation ou un mentor..."><kbd>⌘ K</kbd></label><div class="top-actions"><button class="icon-btn dot"><i data-icon="bell"></i></button><?php $accountContext='app';require __DIR__.'/../partials/account-dropdown.php'; ?></div></header>
<div class="page"><?= $content ?></div></main></div>
<script src="/public/assets/js/app.js"></script></body></html>
