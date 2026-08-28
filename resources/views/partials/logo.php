<?php $brand = array_merge(['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b','logo'=>null],$_SESSION['brand']??[]); ?>
<a class="brand" href="/" aria-label="Accueil">
    <span class="brand-mark"><?php if(!empty($brand['logo'])): ?><img src="<?= htmlspecialchars($brand['logo']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:inherit"><?php else: ?><svg viewBox="0 0 40 40" aria-hidden="true"><path d="M8 8h10v10H8zM22 8h10v10H22zM8 22h10v10H8z"/><path d="M22 22h10v10H22z" opacity=".42"/></svg><?php endif ?></span>
    <span><strong><?= htmlspecialchars($brand['name']) ?></strong><small>Institut de formation</small></span>
</a>
