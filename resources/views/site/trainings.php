<?php
$sectors=[];$modes=[];foreach($trainings as $item){$sectors[$item['sector']]=($sectors[$item['sector']]??0)+1;$modes[$item['mode']]=($modes[$item['mode']]??0)+1;}
?>
<section class="inner-hero catalog-hero">
    <div class="catalog-hero-topline">
        <p class="site-kicker">CATALOGUE DES FORMATIONS</p>
        <?php $shareTitle='Catalogue des formations IFMAP';$shareText='Découvrez les formations professionnelles IFMAP.';$shareCompact=false;$shareIconOnly=false;$shareUrl='';require __DIR__.'/../partials/share-menu.php'; ?>
    </div>
    <h1>Trouvez la formation qui<br>fait avancer votre projet.</h1>
    <p>Explorez les parcours IFMAP par métier, filière, modalité ou budget et accédez rapidement à la formation adaptée à votre objectif.</p>
    <div class="catalog-hero-search"><span aria-hidden="true">⌕</span><input data-training-search placeholder="Rechercher une compétence, une formation ou une filière…" autocomplete="off"><kbd>⌘ K</kbd></div>
</section>

<section class="catalog-layout modern-catalog" data-training-catalog>
    <aside class="site-filters" data-filter-panel>
        <div class="filter-heading"><div><small>AFFINER</small><h2>Filtres</h2></div><button type="button" data-reset-filters>Réinitialiser</button></div>
        <div class="filter-group"><h3>Filière</h3><?php foreach($sectors as $sector=>$count): ?><label class="modern-check"><input type="checkbox" name="sector" value="<?= htmlspecialchars(mb_strtolower($sector)) ?>"><span class="check-ui">✓</span><span><?= htmlspecialchars($sector) ?></span><b><?= $count ?></b></label><?php endforeach ?></div>
        <div class="filter-group"><h3>Modalité</h3><?php foreach($modes as $mode=>$count): ?><label class="modern-check"><input type="checkbox" name="mode" value="<?= htmlspecialchars(mb_strtolower($mode)) ?>"><span class="check-ui">✓</span><span><?= htmlspecialchars($mode) ?></span><b><?= $count ?></b></label><?php endforeach ?></div>
        <div class="filter-group"><h3>Budget</h3><label class="price-field"><span>Prix maximum</span><div><input type="number" min="0" step="5000" data-max-price placeholder="Sans limite"><b>FCFA</b></div></label></div>
    </aside>

    <div class="catalog-main">
        <div class="catalog-tools">
            <div><strong><span data-result-count><?= count($trainings) ?></span> formation(s)</strong><small data-result-label>correspondent à votre recherche</small></div>
            <button class="mobile-filter-button" type="button" data-toggle-filters>☷ Filtres</button>
            <div class="catalog-tools-actions">
                <?php $shareTitle='Catalogue des formations IFMAP';$shareText='Découvrez les formations professionnelles IFMAP.';$shareCompact=true;$shareIconOnly=false;$shareUrl='';require __DIR__.'/../partials/share-menu.php'; ?>
                <label class="modern-select"><span>Trier par</span><select data-training-sort><option value="relevance">Pertinence</option><option value="price-asc">Prix croissant</option><option value="price-desc">Prix décroissant</option><option value="title">Nom A–Z</option></select></label>
            </div>
        </div>
        <div class="active-filters" data-active-filters hidden></div>
        <div class="training-grid" data-training-grid>
            <?php foreach($trainings as $course): $courseUrl='/formations/'.urlencode($course['slug']); ?>
            <article class="training-card" data-title="<?= htmlspecialchars(mb_strtolower($course['title'].' '.$course['sector'])) ?>" data-sector="<?= htmlspecialchars(mb_strtolower($course['sector'])) ?>" data-mode="<?= htmlspecialchars(mb_strtolower($course['mode'])) ?>" data-price="<?= (int)$course['price'] ?>">
                <div class="training-card-share"><?php $shareTitle=$course['title'].' — IFMAP';$shareText='Découvrez la formation '.$course['title'].' sur IFMAP.';$shareCompact=true;$shareIconOnly=true;$shareUrl=$courseUrl;require __DIR__.'/../partials/share-menu.php'; ?></div>
                <a href="<?= $courseUrl ?>" class="training-image <?= htmlspecialchars($course['tone']) ?>">
                    <span class="mode-badge"><?= htmlspecialchars($course['mode']) ?></span>
                    <?php if(!empty($course['thumbnail'])): ?><img class="training-thumbnail" src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>"><?php else: ?><span class="course-mark">IF</span><small><?= htmlspecialchars($course['sector']) ?></small><?php endif ?>
                </a>
                <div>
                    <small class="card-sector"><?= htmlspecialchars($course['sector']) ?></small>
                    <h2><a href="<?= $courseUrl ?>"><?= htmlspecialchars($course['title']) ?></a></h2>
                    <p>◷ <?= htmlspecialchars($course['duration']) ?> <span>·</span> Attestation incluse</p>
                    <footer>
                        <div><small>FRAIS D’INSCRIPTION</small><strong><?= number_format($course['price'],0,',',' ') ?> FCFA</strong></div>
                        <a class="training-card-cta" href="<?= $courseUrl ?>"><span>Voir la formation</span><i aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></i></a>
                    </footer>
                </div>
            </article>
            <?php endforeach ?>
        </div>
        <div class="catalog-empty" data-catalog-empty hidden><span>⌕</span><h2>Aucune formation trouvée</h2><p>Essayez une autre recherche ou réinitialisez les filtres.</p><button class="site-btn primary" data-reset-filters>Réinitialiser</button></div>
    </div>
</section>
