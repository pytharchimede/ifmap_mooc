<section class="admin-welcome">
    <div>
        <p class="eyebrow">MODIFICATION</p>
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <p>Modifiez les informations commerciales et les prérequis sans perdre le programme.</p>
    </div>
    <div>
        <a class="btn secondary" href="/admin/cours/programme?course=<?= $course['id'] ?>">Programme</a>
        <a class="btn secondary" href="/admin/cours">← Retour</a>
    </div>
</section>

<form class="panel admin-editor" method="post" action="/admin/cours/modifier" enctype="multipart/form-data">
    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
    <div class="editor-section">
        <h3>Informations générales</h3>
        <div class="field-grid">
            <label class="full">Titre *
                <input name="title" maxlength="190" value="<?= htmlspecialchars($course['title']) ?>" required>
            </label>
            <label class="full">Description
                <textarea name="description" rows="5"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
            </label>
            <label class="full">Vignette de la formation
                <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp">
                <small><?= !empty($course['thumbnail']) ? 'Une vignette est déjà enregistrée. Sélectionnez une image pour la remplacer.' : 'JPG, PNG ou WebP · 5 Mo maximum' ?></small>
                <?php if (!empty($course['thumbnail'])): ?>
                    <img class="thumbnail-preview" src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="Vignette actuelle de la formation">
                <?php endif ?>
            </label>
            <label class="full">Formateur
                <select name="instructor_id">
                    <option value="">Aucun formateur affecté</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?= (int) $instructor['id'] ?>" <?= (int) ($course['instructor_id'] ?? 0) === (int) $instructor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($instructor['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Filière
                <input name="sector" value="<?= htmlspecialchars($course['category']) ?>">
            </label>
            <label>Mode
                <select name="mode">
                    <?php foreach (['Présentiel', 'En ligne', 'Mixte'] as $mode): ?>
                        <option <?= $course['mode'] === $mode ? 'selected' : '' ?>><?= $mode ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Durée
                <input name="duration" value="<?= htmlspecialchars($course['duration_label']) ?>">
            </label>
            <label>Prix FCFA
                <input type="number" name="price" min="0" value="<?= (int) $course['price'] ?>">
            </label>
            <label>Statut
                <select name="status">
                    <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Publiée</option>
                    <option value="archived" <?= $course['status'] === 'archived' ? 'selected' : '' ?>>Archivée</option>
                </select>
            </label>
        </div>
    </div>

    <div class="editor-section prerequisite-editor">
        <h3>Prérequis</h3>
        <p>Ajoutez, corrigez ou supprimez les prérequis oubliés lors de la création.</p>
        <div class="prerequisite-columns">
            <div>
                <h4>Compétences nécessaires</h4>
                <div data-skill-list>
                    <?php foreach ($skills ?: [''] as $skill): ?>
                        <div class="repeat-field">
                            <input name="prerequisite_skills[]" value="<?= htmlspecialchars($skill) ?>" placeholder="Ex. Connaître les règles HSE">
                            <button type="button" data-remove-field>×</button>
                        </div>
                    <?php endforeach ?>
                </div>
                <button type="button" class="btn secondary" data-add-skill>+ Ajouter une compétence</button>
            </div>
            <div>
                <h4>Formations préalables</h4>
                <div data-course-list>
                    <?php foreach ($requiredCourses ?: [''] as $required): ?>
                        <div class="repeat-field">
                            <select name="prerequisite_courses[]">
                                <option value="">Aucune</option>
                                <?php foreach ($availableCourses as $available): ?>
                                    <option value="<?= $available['id'] ?>" <?= (string) $required === (string) $available['id'] ? 'selected' : '' ?>><?= htmlspecialchars($available['title']) ?></option>
                                <?php endforeach ?>
                            </select>
                            <button type="button" data-remove-field>×</button>
                        </div>
                    <?php endforeach ?>
                </div>
                <button type="button" class="btn secondary" data-add-course>+ Ajouter une formation</button>
            </div>
        </div>
    </div>

    <footer>
        <a class="btn secondary" href="/admin/cours">Annuler</a>
        <button class="btn primary">Enregistrer les modifications</button>
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
    .dropzone-reset { border: 0; background: transparent; color: #a74739; font-size: 11px; cursor: pointer; }
    .thumbnail-preview { display: block; width: 180px; aspect-ratio: 16 / 9; object-fit: cover; border-radius: 7px; margin-top: 10px; }
    .prerequisite-editor > p { color: var(--muted); }
    .prerequisite-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    .repeat-field { display: flex; gap: 7px; margin: 8px 0; }
    .repeat-field input, .repeat-field select { height: 42px; flex: 1; border: 1px solid var(--line); border-radius: 7px; padding: 0 10px; }
    .repeat-field button { width: 40px; border: 1px solid var(--line); background: #fff; color: #a74739; border-radius: 7px; }
    @media (max-width: 700px) { .prerequisite-columns { grid-template-columns: 1fr; } }
</style>
