<?php
$active=$active??'';
$groups=[
    'pilotage'=>['admin-dashboard','admin-courses','admin-enrollments','admin-users','admin-mentorship'],
    'operations'=>['admin-ticketing','admin-crm','admin-orders','admin-documents','admin-products','admin-coupons'],
    'contenus'=>['admin-testimonials','admin-news','admin-visitors','admin-seo'],
    'communication'=>['admin-chat','admin-auth-notifications','admin-i18n'],
    'configuration'=>['admin-integrations','admin-branding','admin-settings'],
];
$open=fn(string $group): string => in_array($active,$groups[$group]??[],true)?' open':'';
?>
<nav class="main-nav admin-nav" data-admin-nav>
    <a class="admin-nav-home <?= $active==='admin-dashboard'?'active':'' ?>" href="/admin"><i data-icon="grid"></i><span>Vue d’ensemble</span></a>

    <details class="admin-nav-group"<?= $open('pilotage') ?>>
        <summary><span class="nav-group-icon">01</span><span><strong>Pilotage</strong><small>Formation & utilisateurs</small></span><b>⌄</b></summary>
        <div class="admin-nav-links">
            <a class="<?= $active==='admin-courses'?'active':'' ?>" href="/admin/cours"><i data-icon="book"></i>Formations</a>
            <a class="<?= $active==='admin-enrollments'?'active':'' ?>" href="/admin/inscriptions"><i data-icon="users"></i>Inscriptions aux cours</a>
            <a class="<?= $active==='admin-users'?'active':'' ?>" href="/admin/utilisateurs"><i data-icon="users"></i>Utilisateurs</a>
            <a class="<?= $active==='admin-mentorship'?'active':'' ?>" href="/admin/mentorat"><i data-icon="award"></i>Mentorat & coaching</a>
        </div>
    </details>

    <details class="admin-nav-group"<?= $open('operations') ?>>
        <summary><span class="nav-group-icon">02</span><span><strong>Opérations</strong><small>Support, CRM & finances</small></span><b>⌄</b></summary>
        <div class="admin-nav-links">
            <a class="<?= $active==='admin-ticketing'?'active':'' ?>" href="/admin/tickets"><i data-icon="mail"></i>Ticketing & assistance</a>
            <a class="<?= $active==='admin-crm'?'active':'' ?>" href="/admin/crm"><i data-icon="target"></i>CRM & opportunités</a>
            <a class="<?= $active==='admin-orders'?'active':'' ?>" href="/admin/commandes"><i data-icon="chart"></i>Finances & commandes</a>
            <a class="<?= $active==='admin-documents'?'active':'' ?>" href="/admin/documents"><i data-icon="award"></i>Documents & exports</a>
            <a class="<?= $active==='admin-products'?'active':'' ?>" href="/admin/produits"><i data-icon="wallet"></i>Stock & produits</a>
            <a class="<?= $active==='admin-coupons'?'active':'' ?>" href="/admin/coupons"><i data-icon="award"></i>Coupons promotionnels</a>
        </div>
    </details>

    <details class="admin-nav-group"<?= $open('contenus') ?>>
        <summary><span class="nav-group-icon">03</span><span><strong>Contenus & visibilité</strong><small>Éditorial, SEO & audience</small></span><b>⌄</b></summary>
        <div class="admin-nav-links">
            <a class="<?= $active==='admin-testimonials'?'active':'' ?>" href="/admin/temoignages"><i data-icon="play"></i>Témoignages vidéo</a>
            <a class="<?= $active==='admin-news'?'active':'' ?>" href="/admin/actualites"><i data-icon="mail"></i>Actualités & blog</a>
            <a class="<?= $active==='admin-visitors'?'active':'' ?>" href="/admin/visiteurs"><i data-icon="users"></i>Visiteurs</a>
            <a class="<?= $active==='admin-seo'?'active':'' ?>" href="/admin/referencement"><i data-icon="search"></i>Référencement</a>
        </div>
    </details>

    <details class="admin-nav-group"<?= $open('communication') ?>>
        <summary><span class="nav-group-icon">04</span><span><strong>Communication</strong><small>Messages, langues & notifications</small></span><b>⌄</b></summary>
        <div class="admin-nav-links">
            <a class="<?= $active==='admin-chat'?'active':'' ?>" href="/admin/discussions"><i data-icon="users"></i>Discussion instantanée</a>
            <a class="<?= $active==='admin-auth-notifications'?'active':'' ?>" href="/admin/auth-notifications"><i data-icon="mail"></i>OTP · SMS · WhatsApp</a>
            <a class="<?= $active==='admin-i18n'?'active':'' ?>" href="/admin/traductions"><i data-icon="compass"></i>Traductions & langues</a>
        </div>
    </details>

    <details class="admin-nav-group"<?= $open('configuration') ?>>
        <summary><span class="nav-group-icon">05</span><span><strong>Configuration</strong><small>Services & identité IFMAP</small></span><b>⌄</b></summary>
        <div class="admin-nav-links">
            <a class="<?= $active==='admin-integrations'?'active':'' ?>" href="/admin/integrations"><i data-icon="settings"></i>Live & mailing SMTP</a>
            <a class="<?= $active==='admin-branding'?'active':'' ?>" href="/admin/branding"><i data-icon="palette"></i>Identité visuelle</a>
            <a class="<?= $active==='admin-settings'?'active':'' ?>" href="/admin/branding"><i data-icon="settings"></i>Paramètres généraux</a>
        </div>
    </details>
</nav>
