<?php $courseData=array_merge(['title'=>'Formation','category'=>'Formation','mode'=>'Présentiel','duration_label'=>'À définir','price'=>0,'tone'=>'navy','thumbnail'=>null,'description'=>''],$courseData??[]);$modules=$modules??[];$prerequisites=$prerequisites??[];$courseTitle = htmlspecialchars($courseData['title']); ?>
<style>
	.detail-photo { overflow: hidden; }
	.detail-photo img { display: block; width: 100%; height: 100%; min-height: 370px; object-fit: cover; }
	.detail-tabs button { border: 0; border-bottom: 2px solid transparent; padding: 0 0 13px; background: transparent; color: inherit; cursor: pointer; font: inherit; font-size: 10px; white-space: nowrap; }
	.detail-tabs button.active { border-bottom-color: var(--site-accent, #d89b2b); color: var(--site-primary, #123f3a); }
	.course-tab-panel[hidden] { display: none; }
	.course-tab-panel { scroll-margin-top: 24px; }
	.program-module-title { margin: 28px 0 8px; color: var(--site-primary, #123f3a); }
	.lesson-description { margin-top: 10px; color: #50645f; line-height: 1.6; }
	.program-empty { padding: 20px 0; color: #667873; }
	.course-prerequisites { margin-top: 32px; padding-top: 24px; border-top: 1px solid #dfe6e2; }
	.course-prerequisites h3 { margin: 18px 0 8px; font-size: 15px; }
	.course-prerequisites ul { margin: 0; padding-left: 20px; color: #50645f; line-height: 1.8; }
	.course-info-section { margin-top: 32px; padding-top: 24px; border-top: 1px solid #dfe6e2; scroll-margin-top: 24px; }
	.course-info-section h2 { margin-bottom: 10px; }
	.course-info-section p, .course-info-section li { color: #50645f; line-height: 1.7; }
	.course-info-section ul { padding-left: 20px; }
	.instructor-profile { display: flex; align-items: center; gap: 16px; margin: 18px 0; }
	.instructor-avatar { display: grid; place-items: center; width: 64px; height: 64px; flex: 0 0 64px; overflow: hidden; border-radius: 50%; background: #dcebe4; color: #176b48; font-size: 24px; font-weight: 800; }
	.instructor-avatar img { width: 100%; height: 100%; object-fit: cover; }
	.instructor-profile h3 { margin: 0 0 4px; }
	.instructor-profile p { margin: 0; }
	.instructor-specialty { font-weight: 700; color: #176b48 !important; }
	.profile-file { display: inline-block; margin-top: 12px; color: #176b48; font-weight: 700; }
</style>
<section class="detail-hero">
	<div class="detail-copy">
		<a href="/formations" class="breadcrumbs">Formations / <?= htmlspecialchars($courseData['category']) ?></a>
		<div class="detail-badges">
			<span><?= htmlspecialchars($courseData['mode']) ?></span>
			<span>CERTIFIANTE</span>
		</div>
		<h1><?= $courseTitle ?></h1>
		<p><?= htmlspecialchars($courseData['description'] ?: 'Un parcours pratique conçu pour développer les compétences professionnelles sur le terrain.') ?></p>
		<div class="detail-meta">
			<span><b><?= htmlspecialchars($courseData['duration_label']) ?></b><small>Durée</small></span>
			<span><b>Yopougon</b><small>Lieu</small></span>
			<span><b>Français</b><small>Langue</small></span>
		</div>
	</div>
	<div class="detail-photo <?= htmlspecialchars($courseData['tone']) ?>">
		<?php if (!empty($courseData['thumbnail'])): ?>
			<img src="<?= htmlspecialchars($courseData['thumbnail']) ?>" alt="Vignette de <?= $courseTitle ?>">
		<?php else: ?>
			<span>FORMATION<br>EN CONDITIONS RÉELLES</span>
		<?php endif ?>
	</div>
</section>

<section class="detail-layout">
	<article class="program">
		<div class="detail-tabs">
			<button class="active" type="button" data-detail-tab="programme" aria-selected="true">Programme</button>
			<button type="button" data-detail-tab="objectifs" aria-selected="false">Objectifs</button>
			<button type="button" data-detail-tab="public" aria-selected="false">Public concerné</button>
			<button type="button" data-detail-tab="formateur" aria-selected="false">Formateur</button>
		</div>
		<div id="programme" class="course-tab-panel">
		<h2>Ce que vous allez apprendre</h2>
		<p><?= htmlspecialchars($courseData['description'] ?: 'Un parcours intensif et concret pour être immédiatement opérationnel sur site.') ?></p>
		<?php if ($modules): ?>
			<?php foreach ($modules as $moduleIndex => $module): ?>
				<h3 class="program-module-title"><?= $moduleIndex + 1 ?>. <?= htmlspecialchars($module['title']) ?></h3>
				<?php foreach ($module['lessons'] as $lessonIndex => $lesson): ?>
					<div class="program-line">
						<span><?= str_pad((string) ($lessonIndex + 1), 2, '0', STR_PAD_LEFT) ?></span>
						<div>
							<h3><?= htmlspecialchars($lesson['title']) ?></h3>
							<p><?= (int) $lesson['duration'] ?> minutes · <?= htmlspecialchars(ucfirst($lesson['type'])) ?></p>
							<?php if ($lesson['type'] === 'text' && !empty($lesson['content'])): ?>
								<div class="lesson-description"><?= $lesson['content'] ?></div>
							<?php endif ?>
						</div>
						<b>⌄</b>
					</div>
				<?php endforeach ?>
			<?php endforeach ?>
		<?php else: ?>
			<p class="program-empty">Le programme détaillé sera bientôt disponible.</p>
		<?php endif ?>
		<?php $skillPrerequisites = array_filter($prerequisites, fn($item) => $item['type'] === 'skill'); ?>
		<?php $coursePrerequisites = array_filter($prerequisites, fn($item) => $item['type'] === 'course'); ?>
		<?php if ($skillPrerequisites || $coursePrerequisites): ?>
			<section class="course-prerequisites">
				<h2>Prérequis</h2>
				<?php if ($skillPrerequisites): ?><h3>Compétences nécessaires</h3><ul><?php foreach ($skillPrerequisites as $item): ?><li><?= htmlspecialchars($item['skill_name']) ?></li><?php endforeach ?></ul><?php endif ?>
				<?php if ($coursePrerequisites): ?><h3>Formations préalables</h3><ul><?php foreach ($coursePrerequisites as $item): ?><li><?= htmlspecialchars($item['required_title']) ?></li><?php endforeach ?></ul><?php endif ?>
			</section>
		<?php endif ?>
		</div>
		<section id="objectifs" class="course-tab-panel course-info-section" hidden>
			<h2>Objectifs de la formation</h2>
			<p>À l’issue de cette formation, vous disposerez des méthodes et des compétences nécessaires pour appliquer les bonnes pratiques dans votre environnement professionnel.</p>
			<ul>
				<li>Comprendre les fondamentaux du métier et son environnement.</li>
				<li>Utiliser les outils et méthodes adaptés aux situations rencontrées.</li>
				<li>Gagner en autonomie et en efficacité sur le terrain.</li>
			</ul>
		</section>
		<section id="public" class="course-tab-panel course-info-section" hidden>
			<h2>Public concerné</h2>
			<p>Cette formation s’adresse aux professionnels, responsables, collaborateurs et personnes souhaitant développer leurs compétences dans le domaine <?= htmlspecialchars($courseData['category']) ?>.</p>
		</section>
		<section id="formateur" class="course-tab-panel course-info-section" hidden>
			<h2>Formateur</h2>
			<?php if (!empty($courseData['instructor_name'])): ?>
				<div class="instructor-profile">
					<div class="instructor-avatar">
						<?php if (!empty($courseData['instructor_avatar'])): ?><img src="<?= htmlspecialchars($courseData['instructor_avatar']) ?>" alt="Photo de <?= htmlspecialchars($courseData['instructor_name']) ?>"><?php else: ?><?= htmlspecialchars(strtoupper(substr($courseData['instructor_name'], 0, 1))) ?><?php endif ?>
					</div>
					<div>
						<h3><?= htmlspecialchars($courseData['instructor_name']) ?></h3>
						<?php if (!empty($courseData['instructor_specialty'])): ?><p class="instructor-specialty"><?= htmlspecialchars($courseData['instructor_specialty']) ?></p><?php endif ?>
						<?php if (!empty($courseData['instructor_email'])): ?><p><?= htmlspecialchars($courseData['instructor_email']) ?><?php if (!empty($courseData['instructor_phone'])): ?> · <?= htmlspecialchars($courseData['instructor_phone']) ?><?php endif ?></p><?php endif ?>
					</div>
				</div>
				<?php if (!empty($courseData['instructor_bio'])): ?><p><?= nl2br(htmlspecialchars($courseData['instructor_bio'])) ?></p><?php endif ?>
				<?php if (!empty($courseData['instructor_cv'])): ?><a class="profile-file" href="<?= htmlspecialchars($courseData['instructor_cv']) ?>" target="_blank" rel="noopener">Consulter le CV du formateur</a><?php endif ?>
			<?php else: ?>
				<p>Le formateur de cette formation sera bientôt présenté.</p>
			<?php endif ?>
		</section>
		<div class="included">
			<strong>✓ Attestation de formation</strong>
			<span>✓ Supports pédagogiques</span>
			<span>✓ Cas pratiques terrain</span>
		</div>
	</article>

	<aside class="buy-card">
		<div class="session-label">PROCHAINE SESSION</div>
		<h3>16 — 19 septembre 2026</h3>
		<p>Base IFMAP · Yopougon Niangon</p>
		<hr>
		<div class="price">
			<strong><?= number_format((int) $courseData['price'], 0, ',', ' ') ?> FCFA</strong>
			<small>par participant</small>
		</div>
		<form method="post" action="/panier/ajouter">
			<input type="hidden" name="type" value="training">
			<input type="hidden" name="course_id" value="<?= (int) $courseData['id'] ?>">
			<button class="site-btn gold wide">S’inscrire et payer en ligne →</button>
		</form>
		<?php if (!empty($_SESSION['user'])): ?><form method="post" action="/academie/inscrire"><input type="hidden" name="course_id" value="<?= (int)$courseData['id'] ?>"><button class="site-btn outline wide">M’inscrire directement au cours</button></form><?php else: ?><a class="site-btn outline wide" href="/inscription?course=<?= (int)$courseData['id'] ?>">Créer mon compte et me préinscrire</a><?php endif ?>
		<p class="safe">🔒 Paiement sécurisé · Accès immédiat</p>
		<div class="payment-list"><span>Orange<br><b>Money</b></span><span>MTN<br><b>MoMo</b></span><span>Moov<br><b>Money</b></span><span>Wave</span><span>VISA</span></div>
		<div class="access-note"><span>↗</span><p><strong>Accès automatique</strong><br>Après paiement, la formation apparaît dans votre espace « Mes formations ».</p></div>
	</aside>
</section>
