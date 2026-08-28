<section class="admin-welcome">
    <div>
        <h1>Formations</h1>
        <p>Créez, organisez et publiez vos contenus pédagogiques.</p>
    </div>
    <a class="btn primary" href="/admin/cours/nouveau">+ Nouvelle formation</a>
</section><section class="panel">
    <div class="table-toolbar">
        <label class="search">
            <i data-icon="search"></i>
            <input data-table-search placeholder="Rechercher une formation...">
        </label>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>FORMATION</th>
                    <th>FILIÈRE</th>
                    <th>MODE</th>
                    <th>PRIX</th>
                    <th>STATUT</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($courses as $course): ?>
                <tr data-search-row><td>
                    <div class="table-course">
                        <span class="mini-cover <?= htmlspecialchars($course['tone']??'indigo') ?>">IF</span>
                        <div><strong><?= htmlspecialchars($course['title']) ?></strong><small><?= htmlspecialchars($course['duration']) ?></small></div></div></td><td><?= htmlspecialchars($course['sector']) ?></td><td><?= htmlspecialchars($course['mode']) ?></td>
                        <td><?= number_format((int)$course['price'],0,',',' ') ?> F</td>
                        <td><span class="status <?= ($course['status']??'published')==='published'?'published':'draft' ?>"><?= ($course['status']??'published')==='published'?'Publiée':'Brouillon' ?></span></td>
                        <td>
                            <form method="post" action="/admin/cours/supprimer" onsubmit="return confirm('Supprimer cette formation ?')">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($course['id']) ?>">
                                <button class="text-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr><?php endforeach ?>
                </tbody>
            </table>
        </div><?php if(!$courses): ?>
        <div class="admin-empty">Aucune formation. Créez votre premier contenu.</div>
        <?php endif ?>
    </section>
