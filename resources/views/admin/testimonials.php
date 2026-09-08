<?php $counts=['pending'=>0,'approved'=>0,'rejected'=>0];foreach($testimonials as $item){$counts[$item['status']]=($counts[$item['status']]??0)+1;} ?>
<section class="admin-testimonials">
<div class="section-head"><div><p class="eyebrow">PREUVE SOCIALE</p><h1>Témoignages vidéo</h1><p>Validez uniquement les témoignages qui reflètent l’expérience IFMAP. Les vidéos approuvées apparaissent automatiquement sur le site.</p></div></div>
<div class="stats-grid"><article><span class="stat-icon orange"><i data-icon="clock"></i></span><div><small>EN ATTENTE</small><strong><?= $counts['pending'] ?></strong><em>À modérer</em></div></article><article><span class="stat-icon green"><i data-icon="award"></i></span><div><small>PUBLIÉS</small><strong><?= $counts['approved'] ?></strong><em>Visibles sur le site</em></div></article><article><span class="stat-icon purple"><i data-icon="more"></i></span><div><small>REFUSÉS</small><strong><?= $counts['rejected'] ?></strong><em>Conservés dans l’historique</em></div></article></div>
<div class="admin-testimonial-grid">
<?php foreach($testimonials as $item): ?>
<article class="admin-testimonial-card">
<?php if($item['preview_url']): ?><video src="<?= htmlspecialchars($item['preview_url']) ?>" controls playsinline preload="metadata"></video><?php else: ?><div class="panel admin-empty">Vidéo indisponible</div><?php endif ?>
<div class="admin-testimonial-body">
<span class="status-pill <?= htmlspecialchars($item['status']) ?>"><?= htmlspecialchars(strtoupper($item['status'])) ?></span><?php if(!empty($item['featured'])): ?> <span class="status-pill approved">MIS EN AVANT</span><?php endif ?>
<h3><?= htmlspecialchars($item['name']) ?></h3><p><?= htmlspecialchars($item['email']) ?><br><?= htmlspecialchars($item['course_title']??'Formation IFMAP') ?> · <?= (int)$item['duration_seconds'] ?> s</p>
<?php if($item['caption']): ?><blockquote>“<?= htmlspecialchars($item['caption']) ?>”</blockquote><?php endif ?>
<?php if($item['status']==='rejected'&&$item['rejection_reason']): ?><p><strong>Motif :</strong> <?= htmlspecialchars($item['rejection_reason']) ?></p><?php endif ?>
<div class="admin-testimonial-actions">
<?php if($item['status']!=='approved'): ?><form method="post" action="/admin/temoignages/moderer"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="action" value="approve"><button class="approve-btn">✓ Publier</button></form><?php else: ?><form method="post" action="/admin/temoignages/moderer"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><input type="hidden" name="action" value="unpublish"><button class="muted-btn">Retirer du site</button></form><?php endif ?>
<?php if($item['status']!=='rejected'): ?><form method="post" action="/admin/temoignages/moderer"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><input name="reason" maxlength="500" placeholder="Motif du refus"><button class="reject-btn" name="action" value="reject">Refuser</button></form><?php endif ?>
<?php if($item['status']==='approved'): ?><form method="post" action="/admin/temoignages/moderer"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="feature-btn" name="action" value="<?= $item['featured']?'unfeature':'feature' ?>"><?= $item['featured']?'Retirer la mise en avant':'★ Mettre en avant' ?></button></form><?php endif ?>
</div></div></article>
<?php endforeach ?>
<?php if(!$testimonials): ?><div class="panel admin-empty">Aucun témoignage vidéo reçu pour le moment.</div><?php endif ?>
</div></section>
