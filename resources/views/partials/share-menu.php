<?php
$shareTitle = trim((string)($shareTitle ?? 'IFMAP'));
$shareText = trim((string)($shareText ?? 'Découvrez cette page IFMAP.'));
$shareCompact = !empty($shareCompact);
?>
<div class="share-menu" data-share-menu data-share-title="<?= htmlspecialchars($shareTitle, ENT_QUOTES, 'UTF-8') ?>" data-share-text="<?= htmlspecialchars($shareText, ENT_QUOTES, 'UTF-8') ?>">
    <button class="share-menu-trigger <?= $shareCompact ? 'is-compact' : '' ?>" type="button" data-share-trigger aria-haspopup="dialog" aria-expanded="false">
        <span class="share-menu-trigger-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M18 8a3 3 0 1 0-2.83-4A3 3 0 0 0 18 8ZM6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm12 7a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM8.62 10.48l6.76-3.96M8.62 13.52l6.76 3.96" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span class="share-menu-trigger-copy"><strong>Partager</strong><?php if(!$shareCompact): ?><small>Choisir un canal</small><?php endif ?></span>
    </button>
    <div class="share-menu-popover" data-share-popover role="dialog" aria-label="Partager cette page" hidden>
        <div class="share-menu-head"><div><small>PARTAGER</small><strong>Choisissez votre canal</strong></div><button type="button" data-share-close aria-label="Fermer">×</button></div>
        <div class="share-menu-grid">
            <button type="button" class="share-option whatsapp" data-share-channel="whatsapp"><span>WA</span><b>WhatsApp</b></button>
            <button type="button" class="share-option facebook" data-share-channel="facebook"><span>f</span><b>Facebook</b></button>
            <button type="button" class="share-option x" data-share-channel="x"><span>𝕏</span><b>X</b></button>
            <button type="button" class="share-option linkedin" data-share-channel="linkedin"><span>in</span><b>LinkedIn</b></button>
            <button type="button" class="share-option email" data-share-channel="email"><span>✉</span><b>Email</b></button>
            <button type="button" class="share-option native" data-share-channel="native"><span>↗</span><b>Autres</b></button>
        </div>
        <button type="button" class="share-copy-link" data-share-channel="copy"><span>⧉</span><div><strong>Copier le lien</strong><small data-share-status>Adresse prête à partager</small></div></button>
    </div>
</div>
