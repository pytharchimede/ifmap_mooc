<section class="admin-welcome">
    <div>
        <h1>Nouvelle formation</h1>
        <p>Configurez les informations, les compétences requises et les formations préalables.</p>
    </div>
    <a class="btn secondary" href="/admin/cours">← Annuler</a>
</section>

<form class="panel admin-editor" method="post" action="/admin/cours/nouveau" enctype="multipart/form-data">
    <div class="editor-section">
        <h3>Informations générales</h3>
        <div class="field-grid">
            <label class="full">Titre de la formation *
                <input name="title" maxlength="190" required placeholder="Ex. Gestion d’une station-service">
            </label>
            <label class="full">Description
                <textarea name="description" rows="4" placeholder="Présentez les objectifs et le contenu de la formation"></textarea>
            </label>
            <label class="full">Vignette de la formation
                <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp">
                <small>JPG, PNG ou WebP · 5 Mo maximum</small>
            </label>
            <label>Filière *
                <select name="sector">
                    <option>Aval pétrolier</option>
                    <option>Énergie solaire</option>
                    <option>Commerce &amp; Marketing</option>
                    <option>Informatique</option>
                    <option>Langues</option>
                </select>
            </label>
            <label>Mode
                <select name="mode">
                    <option>Présentiel</option>
                    <option>En ligne</option>
                    <option>Mixte</option>
                </select>
            </label>
            <label>Durée
                <input name="duration" placeholder="Ex. 4 jours">
            </label>
            <label>Prix FCFA
                <input name="price" type="number" min="0" value="0">
            </label>
            <label>Statut
                <select name="status">
                    <option value="draft">Brouillon</option>
                    <option value="published">Publiée</option>
                </select>
            </label>
        </div>
    </div>

    <div class="editor-section prerequisite-editor">
        <h3>Prérequis de la formation</h3>
        <p>Les prérequis sont informatifs pour les compétences et contrôlés automatiquement pour les formations préalables.</p>
        <div class="prerequisite-columns">
            <div>
                <h4>Compétences nécessaires</h4>
                <div data-skill-list>
                    <div class="repeat-field">
                        <input name="prerequisite_skills[]" placeholder="Ex. Maîtriser les bases de la comptabilité">
                        <button type="button" data-remove-field>×</button>
                    </div>
                </div>
                <button type="button" class="btn secondary" data-add-skill>+ Ajouter une compétence</button>
            </div>
            <div>
                <h4>Formations préalables</h4>
                <p>Sélectionnez une ou plusieurs formations que l’apprenant doit avoir terminées.</p>
                <div data-course-list>
                    <div class="repeat-field">
                        <select name="prerequisite_courses[]">
                            <option value="">Aucune formation obligatoire</option>
                            <?php foreach ($availableCourses as $available): ?>
                                <option value="<?= $available['id'] ?>"><?= htmlspecialchars($available['title']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <button type="button" data-remove-field>×</button>
                    </div>
                </div>
                <button type="button" class="btn secondary" data-add-course>+ Ajouter une formation</button>
            </div>
        </div>
    </div>

    <footer>
        <a class="btn secondary" href="/admin/cours">Annuler</a>
        <button class="btn primary">Créer et construire le programme →</button>
    </footer>
</form>

<style>
    .admin-editor textarea { border: 1px solid var(--line); border-radius: 7px; padding: 12px; resize: vertical; }
    .admin-editor input[type="file"] { padding: 10px 0; }
    .admin-editor label small { display: block; color: var(--muted); font-size: 11px; margin-top: 4px; }
    .search-select { position: relative; }
    .search-select-input { width: 100%; height: 42px; border: 1px solid var(--line); border-radius: 7px; padding: 0 12px; background: #fff; }
    .search-select-input:focus { outline: 2px solid rgba(85, 71, 232, .16); border-color: var(--primary); }
    .search-select-options { position: absolute; z-index: 10; left: 0; right: 0; top: calc(100% + 5px); max-height: 190px; overflow-y: auto; padding: 5px; border: 1px solid var(--line); border-radius: 8px; background: #fff; box-shadow: 0 12px 28px rgba(20, 28, 48, .14); }
    .search-select-options[hidden] { display: none; }
    .search-select-option { display: block; width: 100%; padding: 9px 10px; border: 0; border-radius: 5px; background: transparent; text-align: left; cursor: pointer; }
    .search-select-option:hover { background: #f1f0ff; color: var(--primary); }
    .thumbnail-dropzone { display: grid; justify-items: center; gap: 5px; padding: 22px; border: 1px dashed #b9b7d8; border-radius: 10px; background: #fafaff; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; }
    .thumbnail-dropzone.is-dragging, .thumbnail-dropzone:hover { border-color: var(--primary); background: #f4f2ff; }
    .thumbnail-dropzone.is-invalid { border-color: #c45145; background: #fff7f5; }
    .dropzone-icon { display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; background: #e8e5ff; color: var(--primary); font-size: 20px; font-weight: 700; }
    .thumbnail-dropzone strong { font-size: 12px; }
    .thumbnail-dropzone small { color: var(--muted); }
    .thumbnail-live-preview:empty { display: none; }
    .thumbnail-preview { display: block; width: min(260px, 100%); aspect-ratio: 16 / 9; object-fit: cover; border-radius: 7px; margin-top: 8px; }
    .dropzone-reset { border: 0; background: transparent; color: #a74739; font-size: 11px; cursor: pointer; }
    .prerequisite-editor > p { color: var(--muted); font-size: 11px; }
    .prerequisite-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px; }
    .prerequisite-columns h4 { font-size: 12px; margin-bottom: 5px; }
    .prerequisite-columns > div > p { font-size: 9px; color: var(--muted); }
    .repeat-field { display: flex; gap: 7px; margin: 8px 0; }
    .repeat-field input, .repeat-field select { height: 42px; flex: 1; border: 1px solid var(--line); border-radius: 7px; padding: 0 10px; }
    .repeat-field button { width: 40px; border: 1px solid var(--line); background: #fff; color: #a74739; border-radius: 7px; }
    .prerequisite-columns > .btn { margin-top: 8px; }
    @media (max-width: 700px) { .prerequisite-columns { grid-template-columns: 1fr; } }
</style>
