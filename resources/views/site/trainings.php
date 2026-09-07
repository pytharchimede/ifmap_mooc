<?php
$sectors=[];$modes=[];foreach($trainings as $item){$sectors[$item['sector']]=($sectors[$item['sector']]??0)+1;$modes[$item['mode']]=($modes[$item['mode']]??0)+1;}
?>
<section class="inner-hero catalog-hero">
    <div class="catalog-hero-topline">
        <p class="site-kicker">CATALOGUE DES FORMATIONS</p>
        <button class="catalog-share-button" type="button" data-share-catalog aria-label="Partager le catalogue des formations">
            <span class="catalog-share-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 8a3 3 0 1 0-2.83-4A3 3 0 0 0 18 8ZM6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm12 7a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM8.62 10.48l6.76-3.96M8.62 13.52l6.76 3.96" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="catalog-share-copy"><strong>Partager</strong><small>Envoyer le catalogue</small></span>
        </button>
    </div>
    <h1>Trouvez le parcours fait<br>pour votre évolution.</h1>
    <p>Recherchez par métier, filière ou modalité et inscrivez-vous en quelques étapes.</p>
    <div class="catalog-hero-search"><span>⌕</span><input data-training-search placeholder="Quelle compétence souhaitez-vous développer ?" autocomplete="off"><kbd>⌘ K</kbd></div>
    <div class="catalog-share-feedback" data-share-feedback role="status" aria-live="polite"></div>
</section>
<section class="catalog-layout modern-catalog" data-training-catalog>
<aside class="site-filters" data-filter-panel><div class="filter-heading"><div><small>AFFINER</small><h2>Filtres</h2></div><button type="button" data-reset-filters>Réinitialiser</button></div><div class="filter-group"><h3>Filière</h3><?php foreach($sectors as $sector=>$count): ?><label class="modern-check"><input type="checkbox" name="sector" value="<?= htmlspecialchars(mb_strtolower($sector)) ?>"><span class="check-ui">✓</span><span><?= htmlspecialchars($sector) ?></span><b><?= $count ?></b></label><?php endforeach ?></div><div class="filter-group"><h3>Modalité</h3><?php foreach($modes as $mode=>$count): ?><label class="modern-check"><input type="checkbox" name="mode" value="<?= htmlspecialchars(mb_strtolower($mode)) ?>"><span class="check-ui">✓</span><span><?= htmlspecialchars($mode) ?></span><b><?= $count ?></b></label><?php endforeach ?></div><div class="filter-group"><h3>Budget</h3><label class="price-field"><span>Prix maximum</span><div><input type="number" min="0" step="5000" data-max-price placeholder="Sans limite"><b>FCFA</b></div></label></div></aside>
<div class="catalog-main"><div class="catalog-tools"><div><strong><span data-result-count><?= count($trainings) ?></span> formation(s)</strong><small data-result-label>correspondent à votre recherche</small></div><button class="mobile-filter-button" type="button" data-toggle-filters>☷ Filtres</button><label class="modern-select"><span>Trier par</span><select data-training-sort><option value="relevance">Pertinence</option><option value="price-asc">Prix croissant</option><option value="price-desc">Prix décroissant</option><option value="title">Nom A–Z</option></select></label></div><div class="active-filters" data-active-filters hidden></div><div class="training-grid" data-training-grid><?php foreach($trainings as $course): ?><article class="training-card" data-title="<?= htmlspecialchars(mb_strtolower($course['title'].' '.$course['sector'])) ?>" data-sector="<?= htmlspecialchars(mb_strtolower($course['sector'])) ?>" data-mode="<?= htmlspecialchars(mb_strtolower($course['mode'])) ?>" data-price="<?= (int)$course['price'] ?>"><a href="/formations/<?= urlencode($course['slug']) ?>" class="training-image <?= htmlspecialchars($course['tone']) ?>"><span class="mode-badge"><?= htmlspecialchars($course['mode']) ?></span><?php if(!empty($course['thumbnail'])): ?><img class="training-thumbnail" src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>"><?php else: ?><span class="course-mark">IF</span><small><?= htmlspecialchars($course['sector']) ?></small><?php endif ?></a><div><small class="card-sector"><?= htmlspecialchars($course['sector']) ?></small><h2><a href="/formations/<?= urlencode($course['slug']) ?>"><?= htmlspecialchars($course['title']) ?></a></h2><p>◷ <?= htmlspecialchars($course['duration']) ?> <span>·</span> Attestation incluse</p><footer><div><small>FRAIS D’INSCRIPTION</small><strong><?= number_format($course['price'],0,',',' ') ?> FCFA</strong></div><a href="/formations/<?= urlencode($course['slug']) ?>">Voir la formation →</a></footer></div></article><?php endforeach ?></div><div class="catalog-empty" data-catalog-empty hidden><span>⌕</span><h2>Aucune formation trouvée</h2><p>Essayez une autre recherche ou réinitialisez les filtres.</p><button class="site-btn primary" data-reset-filters>Réinitialiser</button></div></div>
</section>
<script>
(()=>{
    const button=document.querySelector('[data-share-catalog]');
    const feedback=document.querySelector('[data-share-feedback]');
    if(!button)return;
    const show=(message)=>{if(!feedback)return;feedback.textContent=message;feedback.classList.add('show');window.clearTimeout(feedback._timer);feedback._timer=window.setTimeout(()=>feedback.classList.remove('show'),2600)};
    button.addEventListener('click',async()=>{
        const payload={title:'Catalogue des formations IFMAP',text:'Découvrez les formations professionnelles IFMAP.',url:window.location.href};
        try{
            if(navigator.share){await navigator.share(payload);return;}
            await navigator.clipboard.writeText(window.location.href);
            show('Lien du catalogue copié');
        }catch(error){
            if(error && error.name==='AbortError')return;
            try{await navigator.clipboard.writeText(window.location.href);show('Lien du catalogue copié');}catch(_){show('Copiez l’adresse de cette page pour la partager');}
        }
    });
})();
</script>
