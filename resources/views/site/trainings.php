<section class="inner-hero">
    <p class="site-kicker">CATALOGUE 2026</p>
    <h1>Des formations qui préparent<br>aux réalités du terrain.</h1>
    <p>Explorez plus de 240 thèmes conçus avec les professionnels de chaque secteur.</p>
</section>

<style>
    .training-image { overflow: hidden; }
    .training-thumbnail { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>

<section class="catalog-layout">
    <aside class="site-filters">
        <div>
            <h3>Filière</h3>
            <label><input type="checkbox" checked> Toutes les filières <b>240</b></label>
            <label><input type="checkbox"> Aval pétrolier <b>86</b></label>
            <label><input type="checkbox"> Énergie solaire <b>34</b></label>
            <label><input type="checkbox"> Commerce &amp; Marketing <b>48</b></label>
            <label><input type="checkbox"> Informatique <b>42</b></label>
            <label><input type="checkbox"> Langues <b>30</b></label>
        </div>
        <div>
            <h3>Mode</h3>
            <label><input type="checkbox"> Présentiel</label>
            <label><input type="checkbox"> En ligne</label>
            <label><input type="checkbox"> Mixte</label>
        </div>
    </aside>

    <div class="catalog-main">
        <div class="catalog-tools">
            <span><strong>240 formations</strong> disponibles</span>
            <label class="site-search">⌕ <input placeholder="Rechercher une formation"></label>
            <select><option>Trier par : Pertinence</option></select>
        </div>

        <div class="training-grid">
            <?php foreach ($trainings as $course): ?>
                <article class="training-card">
                    <a href="/formations/sous-gerant-station-service" class="training-image <?= htmlspecialchars($course['tone']) ?>">
                        <span class="mode-badge"><?= htmlspecialchars($course['mode']) ?></span>
                        <?php if (!empty($course['thumbnail'])): ?>
                            <img class="training-thumbnail" src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="">
                        <?php else: ?>
                            <b>IFMAP</b>
                            <small><?= htmlspecialchars($course['sector']) ?></small>
                        <?php endif ?>
                    </a>
                    <div>
                        <small><?= htmlspecialchars($course['sector']) ?></small>
                        <h2><a href="/formations/sous-gerant-station-service"><?= htmlspecialchars($course['title']) ?></a></h2>
                        <p>◷ <?= htmlspecialchars($course['duration']) ?> <span>·</span> Certificat inclus</p>
                        <footer>
                            <strong><?= number_format($course['price'], 0, ',', ' ') ?> FCFA</strong>
                            <a href="/formations/sous-gerant-station-service">Découvrir →</a>
                        </footer>
                    </div>
                </article>
            <?php endforeach ?>
        </div>
    </div>
</section>
