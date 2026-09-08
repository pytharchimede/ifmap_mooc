<?php $videoTestimonials=\App\Repositories\TestimonialRepository::approvedForHome(6); ?>
<?php if($videoTestimonials): ?>
<section class="testimonial-showcase" id="temoignages">
<div class="testimonial-wrap">
<div class="testimonial-head"><div><p class="site-kicker">ILS L’ONT VÉCU</p><h2>Des parcours réels. Des voix authentiques.</h2></div><p>Nos apprenants racontent, en moins d’une minute, ce que leur formation IFMAP a changé dans leur pratique et leur confiance sur le terrain.</p></div>
<div class="testimonial-grid">
<?php foreach($videoTestimonials as $item): $initial=mb_strtoupper(mb_substr($item['name']??'A',0,1)); ?>
<article class="testimonial-card">
<div class="testimonial-video"><video src="<?= htmlspecialchars($item['video_url']) ?>" controls playsinline preload="metadata"></video><span class="testimonial-badge"><?= !empty($item['featured'])?'★ TÉMOIGNAGE À LA UNE':'▶ PAROLE D’APPRENANT' ?></span></div>
<div class="testimonial-copy"><div class="testimonial-person"><span class="testimonial-avatar"><?php if(!empty($item['avatar'])): ?><img src="<?= htmlspecialchars($item['avatar']) ?>" alt=""><?php else: ?><?= htmlspecialchars($initial) ?><?php endif ?></span><div><strong><?= htmlspecialchars($item['name']) ?></strong><small><?= htmlspecialchars($item['course_title']??'Apprenant IFMAP') ?></small></div></div><?php if(!empty($item['caption'])): ?><p class="testimonial-quote">“<?= htmlspecialchars($item['caption']) ?>”</p><?php endif ?></div>
</article>
<?php endforeach ?>
</div></div></section>
<?php endif ?>
