<?php
$base = rtrim(str_replace('/index.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/index.php')),'/');
$flash=$_SESSION['testimonial_flash']??null;$error=$_SESSION['testimonial_error']??null;unset($_SESSION['testimonial_flash'],$_SESSION['testimonial_error']);
$statusLabels=['pending'=>'EN ATTENTE DE VALIDATION','approved'=>'PUBLIÉ SUR LE SITE','rejected'=>'REFUSÉ PAR LA MODÉRATION'];
?>
<section class="testimonial-recorder" data-testimonial-recorder data-max-duration="<?= (int)$maxDuration ?>" data-max-bytes="<?= (int)$maxBytes ?>" data-upload-endpoint="<?= htmlspecialchars($base.'/academie/temoignage/upload-url') ?>">
    <div class="testimonial-hero">
        <div><p class="eyebrow">VOTRE EXPÉRIENCE COMPTE</p><h1>Racontez votre parcours en vidéo.</h1><p>Une minute, votre voix, votre expérience. Votre témoignage pourra inspirer de futurs apprenants après validation par l’équipe IFMAP.</p></div>
        <div class="testimonial-rules"><span>🎥 <b>60 secondes max.</b><br>Format court et authentique.</span><span>☁️ <b>Stockage cloud sécurisé</b><br>Envoi direct vers IFMAP Cloud.</span><span>✓ <b>Validation admin</b><br>Rien n’est publié automatiquement.</span></div>
    </div>
    <?php if($flash): ?><div class="testimonial-message success">✓ <?= htmlspecialchars($flash) ?></div><?php endif ?>
    <?php if($error): ?><div class="testimonial-message error">! <?= htmlspecialchars($error) ?></div><?php endif ?>
    <div class="recorder-panel">
        <div class="camera-card">
            <div class="camera-stage">
                <video data-recorder-preview playsinline hidden></video>
                <div class="camera-empty" data-camera-empty><div><span>🎬</span><h3>Prêt à raconter votre expérience ?</h3><p>Placez-vous face à la lumière, regardez la caméra et parlez simplement.</p></div></div>
                <div class="recorder-timer" data-recorder-timer>00:00 / 01:00</div>
            </div>
            <div class="recorder-actions">
                <button class="record-btn primary" type="button" data-record-start>● Commencer l’enregistrement</button>
                <button class="record-btn stop" type="button" data-record-stop hidden>■ Arrêter</button>
                <button class="record-btn secondary" type="button" data-pick-video>↥ Choisir une vidéo</button>
                <input type="file" accept="video/mp4,video/webm,video/quicktime" capture="user" data-video-file hidden>
            </div>
            <div class="upload-progress" data-upload-progress><span></span></div>
            <p class="upload-status" data-upload-status>La caméra et le microphone seront utilisés uniquement pendant l’enregistrement.</p>
        </div>
        <form class="testimonial-form-card" method="post" action="<?= htmlspecialchars($base.'/academie/temoignage') ?>">
            <input type="hidden" name="video_path">
            <input type="hidden" name="duration_seconds">
            <h2>Quelques mots pour accompagner la vidéo</h2>
            <p>Choisissez la formation concernée et ajoutez éventuellement une courte phrase.</p>
            <label>Formation suivie<select name="course_id" required><option value="">Choisir une formation</option><?php foreach($courses as $course): ?><option value="<?= (int)$course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option><?php endforeach ?></select></label>
            <label>Votre phrase clé <textarea name="caption" maxlength="500" placeholder="Ex. Cette formation m’a permis d’être beaucoup plus à l’aise sur le terrain."></textarea></label>
            <label class="testimonial-consent"><input type="checkbox" required> <span>J’autorise IFMAP à diffuser ce témoignage vidéo sur ses supports numériques après validation par un administrateur.</span></label>
            <button class="testimonial-submit" data-testimonial-submit disabled>Envoyer mon témoignage pour validation →</button>
        </form>
    </div>
    <?php if($latest): ?>
    <article class="testimonial-status-card">
        <?php if(!empty($latest['preview_url'])): ?><video src="<?= htmlspecialchars($latest['preview_url']) ?>" controls playsinline preload="metadata"></video><?php endif ?>
        <div><span class="status-pill <?= htmlspecialchars($latest['status']) ?>"><?= htmlspecialchars($statusLabels[$latest['status']]??strtoupper($latest['status'])) ?></span><h3>Votre dernier témoignage</h3><p><?= htmlspecialchars($latest['course_title']??'Formation IFMAP') ?> · <?= (int)$latest['duration_seconds'] ?> s</p><?php if($latest['caption']): ?><blockquote>“<?= htmlspecialchars($latest['caption']) ?>”</blockquote><?php endif ?><?php if($latest['status']==='rejected'&&!empty($latest['rejection_reason'])): ?><p><strong>Motif :</strong> <?= htmlspecialchars($latest['rejection_reason']) ?></p><?php endif ?></div>
    </article>
    <?php endif ?>
</section>
<script src="/public/assets/js/testimonials.js?v=1"></script>
