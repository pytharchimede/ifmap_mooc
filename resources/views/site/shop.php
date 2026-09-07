<?php $categories=array_values(array_filter(array_unique(array_column($products,'category')))); ?>
<section class="inner-hero shop-hero modern-shop-hero">
    <div class="shop-hero-orb orb-one"></div><div class="shop-hero-orb orb-two"></div>
    <p class="site-kicker">BOUTIQUE PROFESSIONNELLE</p>
    <h1>L’équipement qui accompagne<br><em>votre savoir-faire.</em></h1>
    <p>Une sélection fiable et professionnelle, pensée pour le terrain et livrée partout à Abidjan.</p>
    <div class="shop-benefits"><span>✓ Matériel sélectionné</span><span>↗ Livraison suivie</span><span>⌁ Conseil IFMAP</span></div>
</section>
<section class="shop-section modern-shop" data-shop-catalog>
    <header class="shop-toolbar">
        <div><p class="site-kicker">NOTRE SÉLECTION</p><h2>Équipements professionnels</h2><small><b data-product-count><?= count($products) ?></b> produit(s) disponible(s)</small></div>
        <div class="shop-controls"><label class="shop-search"><span>⌕</span><input type="search" data-product-search placeholder="Rechercher un équipement…"></label><div class="filter-chips"><button class="active" type="button" data-category="all">Tous</button><?php foreach($categories as $category): ?><button type="button" data-category="<?= htmlspecialchars(mb_strtolower($category)) ?>"><?= htmlspecialchars($category) ?></button><?php endforeach ?></div></div>
    </header>
    <div class="product-grid modern-product-grid"><?php foreach($products as $product): $promo=!empty($product['promotional_price']); ?>
        <article class="product-card modern-product-card" data-product-category="<?= htmlspecialchars(mb_strtolower($product['category'])) ?>" data-product-searchable="<?= htmlspecialchars(mb_strtolower($product['title'].' '.$product['category'].' '.$product['description'])) ?>">
            <a class="product-image <?= htmlspecialchars($product['tone']) ?>" href="/boutique/<?= rawurlencode($product['slug']) ?>">
                <?php if($product['image']): ?><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>"><?php else: ?><span class="product-placeholder-mark">IF</span><small>ÉQUIPEMENT PROFESSIONNEL</small><?php endif ?>
                <span class="product-category-pill"><?= htmlspecialchars($product['category']) ?></span><?php if($promo): ?><span class="sale-badge">PROMO</span><?php endif ?>
            </a>
            <div class="product-card-body"><div class="product-card-top"><span class="stock <?= $product['stock']==='En stock'?'in':'order' ?>">● <?= htmlspecialchars($product['stock']) ?></span><span class="product-arrow">↗</span></div><h2><a href="/boutique/<?= rawurlencode($product['slug']) ?>"><?= htmlspecialchars($product['title']) ?></a></h2><p><?= htmlspecialchars(mb_substr($product['description']?:'Équipement professionnel sélectionné et contrôlé par IFMAP.',0,115)) ?></p><footer><div><small>PRIX</small><strong><?= $promo?number_format($product['promotional_price'],0,',',' '):($product['price']?number_format($product['price'],0,',',' '):'Sur demande') ?><?= ($promo||$product['price'])?' FCFA':'' ?></strong><?php if($promo): ?><s><?= number_format($product['price'],0,',',' ') ?> FCFA</s><?php endif ?></div><a class="product-cta" href="/boutique/<?= rawurlencode($product['slug']) ?>">Découvrir <span>→</span></a></footer></div>
        </article><?php endforeach ?>
    </div>
    <div class="shop-empty" data-shop-empty hidden><span>⌕</span><h2>Aucun produit trouvé</h2><p>Essayez une autre recherche ou affichez toutes les catégories.</p></div>
</section>
<script>(()=>{const root=document.querySelector('[data-shop-catalog]');if(!root)return;const cards=[...root.querySelectorAll('[data-product-category]')],search=root.querySelector('[data-product-search]'),buttons=[...root.querySelectorAll('[data-category]')],count=root.querySelector('[data-product-count]');let category='all';const apply=()=>{const q=search.value.trim().toLocaleLowerCase();let visible=0;cards.forEach(card=>{const show=(category==='all'||card.dataset.productCategory===category)&&(!q||card.dataset.productSearchable.includes(q));card.hidden=!show;if(show)visible++});count.textContent=visible;root.querySelector('[data-shop-empty]').hidden=visible!==0};buttons.forEach(button=>button.addEventListener('click',()=>{category=button.dataset.category;buttons.forEach(item=>item.classList.toggle('active',item===button));apply()}));search.addEventListener('input',apply)})();</script>
