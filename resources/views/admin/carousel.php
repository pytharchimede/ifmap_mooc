<section class="module-hero"><div><span class="module-kicker">Page d’accueil</span><h1>Carrousel principal</h1><p>Ajoutez et organisez les slides affichées dans la zone d’ouverture du site. Le carrousel public s’active uniquement avec au moins deux slides actives disposant d’une image et d’un CTA principal valide.</p></div><a class="btn secondary" href="/" target="_blank">↗ Voir la page d’accueil</a></section>

<?php if(!empty($flash)): ?><div class="toast">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?>
<?php if(!empty($error)): ?><div class="form-alert error"><?= htmlspecialchars($error) ?></div><?php endif ?>

<section class="module-panel carousel-settings-panel">
    <div class="module-panel-head"><div><span class="module-kicker">Comportement</span><h2>Paramètres globaux</h2></div></div>
    <form method="post" action="/admin/carrousel/parametres" class="modern-form compact-carousel-settings">
        <label class="toggle-line"><input type="checkbox" name="enabled" value="1" <?= ($settings['home_carousel_enabled']??'1')==='1'?'checked':'' ?>><span>Activer le carrousel lorsque les conditions d’affichage sont réunies</span></label>
        <label><span>Délai automatique</span><div class="input-suffix"><input type="number" name="autoplay_ms" min="3000" max="20000" step="500" value="<?= (int)($settings['home_carousel_autoplay_ms']??6500) ?>"><small>ms</small></div></label>
        <button class="btn primary" type="submit">Enregistrer les paramètres</button>
    </form>
</section>

<section class="module-panel">
    <div class="module-panel-head"><div><span class="module-kicker">Nouvelle slide</span><h2>Ajouter un carrousel</h2></div><span class="module-note">JPG, PNG ou WebP · 8 Mo max.</span></div>
    <form method="post" action="/admin/carrousel/enregistrer" enctype="multipart/form-data" class="modern-form carousel-admin-form">
        <div class="carousel-form-grid">
            <label><span>Ordre</span><input type="number" name="position" min="0" value="<?= count($slides)+1 ?>"></label>
            <label><span>Statut</span><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <label class="wide"><span>Surtitre</span><input type="text" name="eyebrow" maxlength="190" required placeholder="INSTITUT DE FORMATION AUX MÉTIERS"></label>
            <label class="wide"><span>Titre principal</span><input type="text" name="title" maxlength="255" required placeholder="Se former. Se faire accompagner."></label>
            <label class="wide"><span>Texte mis en avant</span><input type="text" name="highlighted_text" maxlength="190" placeholder="S’équiper."></label>
            <label class="wide"><span>Description</span><textarea name="description" rows="3" placeholder="Votre texte de présentation..."></textarea></label>
            <label class="wide"><span>Image de fond</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>Le carrousel n’utilisera cette slide que lorsque son image et son CTA principal seront renseignés.</small></label>
            <label><span>Bouton principal</span><input type="text" name="primary_label" maxlength="120" required placeholder="Découvrir les formations"></label>
            <label><span>Lien principal</span><input type="text" name="primary_url" required placeholder="/formations"></label>
            <label><span>Bouton secondaire</span><input type="text" name="secondary_label" maxlength="120" placeholder="Voir la boutique"></label>
            <label><span>Lien secondaire</span><input type="text" name="secondary_url" placeholder="/boutique"></label>
        </div>
        <div class="carousel-stat-editor">
            <?php for($i=1;$i<=3;$i++): ?><div><label><span>Stat <?= $i ?> · Valeur</span><input type="text" name="stat_<?= $i ?>_value" placeholder="<?= $i===1?'240+':($i===2?'6':'15 ans') ?>"></label><label><span>Stat <?= $i ?> · Libellé</span><input type="text" name="stat_<?= $i ?>_label" placeholder="<?= $i===1?'Thèmes de formation':($i===2?'Filières métiers':'D’expertise terrain') ?>"></label></div><?php endfor ?>
        </div>
        <button class="btn primary" type="submit">+ Ajouter la slide</button>
    </form>
</section>

<section class="module-panel">
    <div class="module-panel-head"><div><span class="module-kicker">Slides existantes</span><h2><?= count($slides) ?> élément(s)</h2></div></div>
    <?php if(!$slides): ?><div class="module-empty">Aucune slide enregistrée.</div><?php endif ?>
    <div class="carousel-admin-list">
        <?php foreach($slides as $slide): ?>
        <article class="carousel-admin-card">
            <div class="carousel-admin-preview <?= empty($slide['image_path'])?'is-empty':'' ?>" <?php if(!empty($slide['image_path'])): ?>style="background-image:linear-gradient(90deg,rgba(0,0,0,.66),rgba(0,0,0,.18)),url('<?= htmlspecialchars($slide['image_path']) ?>')"<?php endif ?>>
                <span class="carousel-admin-status <?= $slide['status']==='active'?'active':'inactive' ?>"><?= $slide['status']==='active'?'Active':'Inactive' ?></span>
                <div><small><?= htmlspecialchars($slide['eyebrow']) ?></small><strong><?= htmlspecialchars($slide['title']) ?></strong><?php if(!empty($slide['highlighted_text'])): ?><em><?= htmlspecialchars($slide['highlighted_text']) ?></em><?php endif ?></div>
            </div>
            <form method="post" action="/admin/carrousel/enregistrer" enctype="multipart/form-data" class="modern-form carousel-admin-form edit">
                <input type="hidden" name="id" value="<?= (int)$slide['id'] ?>">
                <div class="carousel-form-grid">
                    <label><span>Ordre</span><input type="number" name="position" min="0" value="<?= (int)$slide['position'] ?>"></label>
                    <label><span>Statut</span><select name="status"><option value="active" <?= $slide['status']==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $slide['status']==='inactive'?'selected':'' ?>>Inactive</option></select></label>
                    <label class="wide"><span>Surtitre</span><input type="text" name="eyebrow" value="<?= htmlspecialchars($slide['eyebrow']) ?>" required></label>
                    <label class="wide"><span>Titre</span><input type="text" name="title" value="<?= htmlspecialchars($slide['title']) ?>" required></label>
                    <label class="wide"><span>Texte mis en avant</span><input type="text" name="highlighted_text" value="<?= htmlspecialchars($slide['highlighted_text']??'') ?>"></label>
                    <label class="wide"><span>Description</span><textarea name="description" rows="3"><?= htmlspecialchars($slide['description']??'') ?></textarea></label>
                    <label class="wide"><span>Remplacer l’image</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><?php if(empty($slide['image_path'])): ?><small class="danger-text">Aucune image : cette slide ne peut pas encore activer le carrousel.</small><?php else: ?><small><?= htmlspecialchars($slide['image_path']) ?></small><?php endif ?></label>
                    <label><span>Bouton principal</span><input type="text" name="primary_label" value="<?= htmlspecialchars($slide['primary_label']??'') ?>" required></label>
                    <label><span>Lien principal</span><input type="text" name="primary_url" value="<?= htmlspecialchars($slide['primary_url']??'') ?>" required></label>
                    <label><span>Bouton secondaire</span><input type="text" name="secondary_label" value="<?= htmlspecialchars($slide['secondary_label']??'') ?>"></label>
                    <label><span>Lien secondaire</span><input type="text" name="secondary_url" value="<?= htmlspecialchars($slide['secondary_url']??'') ?>"></label>
                </div>
                <div class="carousel-stat-editor">
                    <?php for($i=1;$i<=3;$i++): ?><div><label><span>Stat <?= $i ?> · Valeur</span><input type="text" name="stat_<?= $i ?>_value" value="<?= htmlspecialchars($slide['stat_'.$i.'_value']??'') ?>"></label><label><span>Stat <?= $i ?> · Libellé</span><input type="text" name="stat_<?= $i ?>_label" value="<?= htmlspecialchars($slide['stat_'.$i.'_label']??'') ?>"></label></div><?php endfor ?>
                </div>
                <div class="carousel-card-actions"><button class="btn primary" type="submit">Enregistrer</button></div>
            </form>
            <form method="post" action="/admin/carrousel/supprimer" onsubmit="return confirm('Supprimer définitivement cette slide ?')" class="carousel-delete-form"><input type="hidden" name="id" value="<?= (int)$slide['id'] ?>"><button class="btn danger" type="submit">Supprimer</button></form>
        </article>
        <?php endforeach ?>
    </div>
</section>

<style>
.carousel-settings-panel{margin-bottom:18px}.compact-carousel-settings{display:flex;align-items:end;gap:18px;flex-wrap:wrap}.compact-carousel-settings>label{min-width:230px}.toggle-line{display:flex!important;align-items:center;gap:10px;min-width:min(540px,100%)!important}.toggle-line input{width:auto!important}.input-suffix{display:flex;align-items:center;gap:7px}.input-suffix input{max-width:150px}.carousel-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.carousel-form-grid .wide{grid-column:1/-1}.carousel-stat-editor{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:16px 0}.carousel-stat-editor>div{background:#f6f7f8;border:1px solid #e6e8ec;border-radius:12px;padding:12px}.carousel-admin-list{display:grid;gap:18px}.carousel-admin-card{border:1px solid #e4e7ec;border-radius:18px;overflow:hidden;background:#fff;position:relative}.carousel-admin-preview{height:190px;background:linear-gradient(135deg,var(--primary),#171b2a);background-size:cover;background-position:center;color:#fff;padding:22px;display:flex;align-items:end;position:relative}.carousel-admin-preview.is-empty:after{content:'IMAGE À AJOUTER';position:absolute;inset:0;display:grid;place-items:center;font:800 20px Manrope;color:#ffffff33}.carousel-admin-preview>div{position:relative;z-index:2;display:flex;flex-direction:column;max-width:700px}.carousel-admin-preview small{font-weight:800;letter-spacing:1.5px;color:var(--accent)}.carousel-admin-preview strong{font:800 26px Manrope;margin-top:5px}.carousel-admin-preview em{font:700 22px Manrope;color:var(--accent);font-style:normal}.carousel-admin-status{position:absolute;top:14px;right:14px;z-index:3;border-radius:999px;padding:6px 10px;font-size:10px;font-weight:800;background:#fff;color:#222}.carousel-admin-status.inactive{opacity:.65}.carousel-admin-form.edit{padding:20px}.carousel-card-actions{display:flex;gap:10px}.carousel-delete-form{position:absolute;right:20px;bottom:20px}.danger-text{color:#a92727!important;font-weight:700}.form-alert.error{padding:13px 15px;background:#fff0f0;border:1px solid #f0caca;color:#9b2323;border-radius:12px;margin-bottom:15px}.module-note{font-size:11px;color:#7a8190}.btn.danger{background:#fff;border:1px solid #e5b9b9;color:#a12828}@media(max-width:850px){.carousel-form-grid,.carousel-stat-editor{grid-template-columns:1fr}.carousel-form-grid .wide{grid-column:auto}.carousel-delete-form{position:static;padding:0 20px 20px}.compact-carousel-settings{align-items:stretch;flex-direction:column}.compact-carousel-settings>label{min-width:0}}
</style>
