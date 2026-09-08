<?php
$pointsJson=htmlspecialchars(json_encode($mapPoints,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),ENT_QUOTES,'UTF-8');
$trendJson=htmlspecialchars(json_encode($trend,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),ENT_QUOTES,'UTF-8');
?>
<section class="analytics-hero">
  <div><span class="analytics-kicker">AUDIENCE • FIRST-PARTY ANALYTICS</span><h1>Comprendre qui visite IFMAP.</h1><p>Audience, retours, provenance, appareils et géographie — sans dépendre d’un outil publicitaire externe.</p></div>
  <div class="analytics-period"><strong><?= htmlspecialchars($filters['from']) ?></strong><span>→</span><strong><?= htmlspecialchars($filters['to']) ?></strong></div>
</section>

<form class="analytics-search" method="get" action="/admin/visiteurs">
  <label><span>Recherche avancée</span><input name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="IP, ville, pays, navigateur, page, identifiant…"></label>
  <label><span>Du</span><input type="date" name="from" value="<?= htmlspecialchars($filters['from']) ?>"></label>
  <label><span>Au</span><input type="date" name="to" value="<?= htmlspecialchars($filters['to']) ?>"></label>
  <label><span>Pays</span><input name="country" maxlength="2" value="<?= htmlspecialchars($filters['country']) ?>" placeholder="CI"></label>
  <label><span>Appareil</span><select name="device"><option value="">Tous</option><?php foreach(['Desktop','Mobile','Tablette'] as $d): ?><option <?= $filters['device']===$d?'selected':'' ?>><?= $d ?></option><?php endforeach ?></select></label>
  <label><span>Type visiteur</span><select name="returning"><option value="">Tous</option><option value="0" <?= $filters['returning']==='0'?'selected':'' ?>>Nouveaux</option><option value="1" <?= $filters['returning']==='1'?'selected':'' ?>>De retour</option></select></label>
  <button class="btn primary">Analyser</button><a class="btn secondary" href="/admin/visiteurs">Réinitialiser</a>
</form>

<div class="analytics-kpis">
  <article><span>Visiteurs uniques</span><strong><?= number_format($stats['unique_visitors'],0,',',' ') ?></strong><small>identifiants first-party distincts</small></article>
  <article><span>Visiteurs de retour</span><strong><?= number_format($stats['returning_visitors'],0,',',' ') ?></strong><small><?= $stats['unique_visitors']?round($stats['returning_visitors']/$stats['unique_visitors']*100):0 ?>% des visiteurs</small></article>
  <article><span>Sessions</span><strong><?= number_format($stats['sessions'],0,',',' ') ?></strong><small><?= number_format($stats['returning_sessions'],0,',',' ') ?> sessions de retour</small></article>
  <article><span>Pages vues</span><strong><?= number_format($stats['pageviews'],0,',',' ') ?></strong><small><?= $stats['pages_per_session'] ?> page(s) / session</small></article>
</div>

<div class="analytics-grid two">
  <section class="analytics-panel map-panel"><div class="panel-title"><div><span>CARTE MONDIALE</span><h2>Où se trouve votre audience ?</h2></div><small>Localisation approximative issue de l’adresse IP</small></div><div id="visitor-world-map" data-points="<?= $pointsJson ?>"></div></section>
  <section class="analytics-panel"><div class="panel-title"><div><span>ÉVOLUTION</span><h2>Trafic dans le temps</h2></div></div><div class="visitor-trend" data-trend="<?= $trendJson ?>"><canvas id="visitor-trend-canvas" width="800" height="320"></canvas></div></section>
</div>

<div class="analytics-grid three">
  <section class="analytics-panel"><div class="panel-title"><div><span>CONTENU</span><h2>Pages les plus vues</h2></div></div><div class="rank-list"><?php foreach($topPages as $i=>$row): ?><div><b><?= $i+1 ?></b><span><strong><?= htmlspecialchars($row['path']) ?></strong><small><?= (int)$row['visitors'] ?> visiteur(s)</small></span><em><?= (int)$row['views'] ?></em></div><?php endforeach ?></div></section>
  <section class="analytics-panel"><div class="panel-title"><div><span>ACQUISITION</span><h2>Sources de trafic</h2></div></div><div class="rank-list"><?php foreach($sources as $i=>$row): ?><div><b><?= $i+1 ?></b><span><strong><?= htmlspecialchars($row['source']) ?></strong></span><em><?= (int)$row['sessions'] ?></em></div><?php endforeach ?></div></section>
  <section class="analytics-panel"><div class="panel-title"><div><span>TECHNOLOGIE</span><h2>Appareils & navigateurs</h2></div></div><div class="device-bars"><?php $max=max(array_column($devices,'value')?:[1]);foreach($devices as $row): ?><label><span><?= htmlspecialchars($row['label']) ?></span><i><b style="width:<?= round($row['value']/$max*100) ?>%"></b></i><strong><?= (int)$row['value'] ?></strong></label><?php endforeach ?></div><hr><div class="browser-pills"><?php foreach($browsers as $row): ?><span><?= htmlspecialchars($row['label']) ?> <b><?= (int)$row['value'] ?></b></span><?php endforeach ?></div></section>
</div>

<section class="analytics-panel countries-panel"><div class="panel-title"><div><span>GÉOGRAPHIE</span><h2>Pays principaux</h2></div></div><div class="country-grid"><?php foreach($countries as $row): ?><article><span><?= htmlspecialchars($row['code']?:'—') ?></span><div><strong><?= htmlspecialchars($row['country']) ?></strong><small><?= (int)$row['visitors'] ?> visiteur(s)</small></div><b><?= (int)$row['sessions'] ?> sessions</b></article><?php endforeach ?></div></section>

<section class="analytics-panel sessions-panel"><div class="panel-title"><div><span>JOURNAL D’AUDIENCE</span><h2>Dernières sessions correspondant aux filtres</h2></div><small>IP et port visibles uniquement dans l’administration</small></div><div class="table-scroll"><table class="analytics-table"><thead><tr><th>Visiteur</th><th>Localisation</th><th>Technologie</th><th>Réseau</th><th>Entrée</th><th>Pages</th><th>Dernière activité</th></tr></thead><tbody><?php foreach($sessions as $s): ?><tr><td><strong><?= $s['is_returning']?'↻ Retour':'● Nouveau' ?></strong><small><?= htmlspecialchars(substr($s['visitor_uuid'],0,10)) ?>…<?php if($s['user_id']): ?> · compte #<?= (int)$s['user_id'] ?><?php endif ?></small></td><td><?= htmlspecialchars(trim(($s['city']?:'').' '.($s['country_name']?:''))?:'Inconnue') ?><small><?= htmlspecialchars($s['timezone']?:'') ?></small></td><td><?= htmlspecialchars($s['device_type']?:'—') ?><small><?= htmlspecialchars(($s['browser']?:'').' · '.($s['os']?:'')) ?></small></td><td><code><?= htmlspecialchars($s['ip_address']?:'—') ?></code><small>port <?= $s['remote_port']?(int)$s['remote_port']:'—' ?></small></td><td><strong><?= htmlspecialchars($s['landing_path']) ?></strong><small><?= htmlspecialchars($s['utm_source']?:($s['referrer']?'Référent':'Direct')) ?></small></td><td><b><?= (int)$s['pageviews'] ?></b></td><td><?= htmlspecialchars($s['last_seen']) ?></td></tr><?php endforeach ?></tbody></table></div></section>
