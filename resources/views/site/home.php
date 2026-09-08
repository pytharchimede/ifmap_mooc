<?php
$carouselSlides=[];$carouselAutoplay=6500;
try{
    $db=\App\Core\Database::connection();
    $enabled=$db->query("SELECT `value` FROM settings WHERE `key`='home_carousel_enabled' LIMIT 1")->fetchColumn();
    $autoplay=$db->query("SELECT `value` FROM settings WHERE `key`='home_carousel_autoplay_ms' LIMIT 1")->fetchColumn();
    if(is_numeric($autoplay))$carouselAutoplay=max(3500,(int)$autoplay);
    if($enabled!=='0'){
        $rows=$db->query("SELECT * FROM home_carousel_slides WHERE status='active' ORDER BY position,id")->fetchAll();
        $carouselSlides=array_values(array_filter($rows,static fn(array $slide):bool=>trim((string)($slide['image_path']??''))!==''&&trim((string)($slide['primary_url']??''))!==''&&trim((string)($slide['primary_label']??''))!==''));
    }
}catch(\Throwable){}
$showCarousel=count($carouselSlides)>=2;
?>
<?php if($showCarousel): ?>
<section class="ifmap-carousel" data-home-carousel data-autoplay="<?= (int)$carouselAutoplay ?>" aria-label="À la une IFMAP">
    <div class="ifmap-carousel-track">
        <?php foreach($carouselSlides as $index=>$slide): ?>
        <article class="ifmap-slide <?= $index===0?'is-active':'' ?>" data-carousel-slide aria-hidden="<?= $index===0?'false':'true' ?>">
            <div class="ifmap-slide-media"><img src="<?= htmlspecialchars($slide['image_path']) ?>" alt="" loading="<?= $index===0?'eager':'lazy' ?>"><span class="ifmap-slide-shade"></span></div>
            <div class="ifmap-slide-content"><div class="ifmap-slide-copy">
                <p class="site-kicker"><?= htmlspecialchars($slide['eyebrow']) ?></p>
                <h1><?= htmlspecialchars($slide['title']) ?><?php if(!empty($slide['highlighted_text'])): ?> <em><?= htmlspecialchars($slide['highlighted_text']) ?></em><?php endif ?></h1>
                <?php if(!empty($slide['description'])): ?><p class="ifmap-slide-description"><?= htmlspecialchars($slide['description']) ?></p><?php endif ?>
                <div class="ifmap-slide-actions"><a class="site-btn gold" href="<?= htmlspecialchars($slide['primary_url']) ?>"><?= htmlspecialchars($slide['primary_label']) ?> <span>→</span></a><?php if(!empty($slide['secondary_label'])&&!empty($slide['secondary_url'])): ?><a class="site-btn ghost" href="<?= htmlspecialchars($slide['secondary_url']) ?>"><?= htmlspecialchars($slide['secondary_label']) ?></a><?php endif ?></div>
                <div class="ifmap-slide-proof">
                    <?php for($i=1;$i<=3;$i++): $value=$slide['stat_'.$i.'_value']??'';$label=$slide['stat_'.$i.'_label']??'';if($value===''&&$label==='')continue; ?><div><strong><?= htmlspecialchars($value) ?></strong><small><?= htmlspecialchars($label) ?></small></div><?php endfor ?>
                </div>
            </div></div>
        </article>
        <?php endforeach ?>
    </div>
    <div class="ifmap-carousel-dots" aria-label="Choisir une diapositive"><?php foreach($carouselSlides as $index=>$slide): ?><button type="button" class="ifmap-carousel-dot <?= $index===0?'is-active':'' ?>" data-carousel-dot aria-label="Diapositive <?= $index+1 ?>" aria-current="<?= $index===0?'true':'false' ?>"></button><?php endforeach ?></div>
    <span class="ifmap-carousel-count" data-carousel-count>01 / <?= str_pad((string)count($carouselSlides),2,'0',STR_PAD_LEFT) ?></span>
    <div class="ifmap-carousel-controls"><button type="button" class="ifmap-carousel-arrow" data-carousel-prev aria-label="Diapositive précédente">←</button><button type="button" class="ifmap-carousel-arrow" data-carousel-next aria-label="Diapositive suivante">→</button></div>
    <button type="button" class="ifmap-carousel-pause" data-carousel-pause aria-label="Mettre le carrousel en pause">Ⅱ</button>
</section>
<?php else: ?>
<section class="site-hero site-hero-fallback"><div class="hero-copy"><p class="site-kicker">INSTITUT DE FORMATION AUX MÉTIERS</p><h1>Se former. Se faire accompagner. <em>S’équiper.</em></h1><p>240 thèmes de formation sur 6 filières, une académie en ligne et des équipements professionnels — réunis sur une seule plateforme.</p><div class="hero-actions"><a class="site-btn gold" href="/formations">Découvrir les formations <span>→</span></a><a class="site-btn ghost" href="/boutique">Voir la boutique</a></div><div class="hero-proof"><div><strong>240+</strong><small>Thèmes de formation</small></div><div><strong>6</strong><small>Filières métiers</small></div><div><strong>15 ans</strong><small>D’expertise terrain</small></div></div></div><div class="hero-visual"><div class="photo-placeholder petrol"><span>IFMAP</span><small>FORMATION TERRAIN</small></div><div class="hero-card"><span>PROCHAINE SESSION</span><strong>Sous-Gérant de Station-Service</strong><small>16 septembre · Yopougon Niangon</small></div></div></section>
<?php endif ?>
<section class="three-paths"><div class="site-section-head"><p class="site-kicker">TOUT IFMAP, AU MÊME ENDROIT</p><h2>Quel est votre besoin aujourd’hui ?</h2></div><div class="path-grid">
<a href="/formations" class="path-card"><div class="path-photo classroom path-illustration"><span class="path-number">01</span><span class="path-modern-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H18a2 2 0 0 1 2 2v12.5a1.5 1.5 0 0 1-1.5 1.5H7a3 3 0 0 0-3 3V5.5Z" stroke="currentColor" stroke-width="1.7"/><path d="M8 7h8M8 11h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span></div><div><small>FORMATIONS PROFESSIONNELLES</small><h3>Développer mes compétences</h3><p>Des parcours pratiques conçus pour les réalités du terrain.</p><b>Voir les formations →</b></div></a>
<a href="/academie" class="path-card"><div class="path-photo online path-illustration"><span class="path-number">02</span><span class="path-modern-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="13" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M9 21h6M12 17v4M9.5 8.5l5 3-5 3v-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span></div><div><small>ACADÉMIE EN LIGNE</small><h3>Apprendre à mon rythme</h3><p>Vos cours, ressources et attestations accessibles partout.</p><b>Accéder à l’académie →</b></div></a>
<a href="/boutique" class="path-card"><div class="path-photo equipment path-illustration"><span class="path-number">03</span><span class="path-modern-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 8h14l-1 12H6L5 8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 10V7a3 3 0 0 1 6 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span></div><div><small>ÉQUIPEMENTS PROFESSIONNELS</small><h3>Équiper mon activité</h3><p>Le matériel essentiel pour les stations-service et professionnels.</p><b>Visiter la boutique →</b></div></a>
</div></section>
<?php require __DIR__.'/../partials/video-testimonials.php'; ?>
<section class="business-band" id="entreprises"><div><p class="site-kicker">SOLUTIONS ENTREPRISES</p><h2>Faites grandir vos équipes avec IFMAP.</h2><p>Formations sur mesure, conseil et accompagnement opérationnel partout en Côte d’Ivoire.</p></div><a class="site-btn light" href="#contact">Parler à un conseiller →</a></section>
