<nav class="main-nav">
    <p>PILOTAGE</p>
    <a class="<?= $active==='admin-dashboard'?'active':'' ?>" href="/admin"><i data-icon="grid"></i>Vue d’ensemble</a>
    <a class="<?= $active==='admin-courses'?'active':'' ?>" href="/admin/cours"><i data-icon="book"></i>Formations</a>
    <a class="<?= $active==='admin-enrollments'?'active':'' ?>" href="/admin/inscriptions"><i data-icon="users"></i>Inscriptions aux cours</a>
    <a class="<?= $active==='admin-users'?'active':'' ?>" href="/admin/utilisateurs"><i data-icon="users"></i>Utilisateurs</a>
    <a class="<?= $active==='admin-orders'?'active':'' ?>" href="/admin/commandes"><i data-icon="chart"></i>Finances & commandes</a>
    <a class="<?= $active==='admin-documents'?'active':'' ?>" href="/admin/documents"><i data-icon="award"></i>Documents & exports</a>
    <a class="<?= $active==='admin-news'?'active':'' ?>" href="/admin/actualites"><i data-icon="mail"></i>Actualités & blog</a>
    <a class="<?= $active==='admin-products'?'active':'' ?>" href="/admin/produits"><i data-icon="wallet"></i>Stock & produits</a>
    <a class="<?= $active==='admin-coupons'?'active':'' ?>" href="/admin/coupons"><i data-icon="award"></i>Coupons promotionnels</a>
    <p>CONFIGURATION</p>
    <a class="<?= $active==='admin-branding'?'active':'' ?>" href="/admin/branding"><i data-icon="palette"></i>Identité visuelle</a>
    <a class="<?= $active==='admin-settings'?'active':'' ?>" href="/admin/branding"><i data-icon="settings"></i>Paramètres</a>
</nav>
