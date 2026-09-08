<?php
try{$i18nSettings=(new \App\Services\SecureSettings())->group('i18n');$locale=\App\Services\I18n::locale();$locales=array_filter(array_map('trim',explode(',',$i18nSettings['supported_locales']??'fr,en')));}catch(\Throwable){$locale='fr';$locales=['fr','en'];}
?>
<form method="post" action="/langue" class="language-switcher" title="Langue de l’interface">
    <span>🌐</span>
    <select name="locale" onchange="this.form.submit()" aria-label="Langue">
        <?php foreach($locales as $code): ?><option value="<?= htmlspecialchars($code) ?>" <?= $locale===$code?'selected':'' ?>><?= htmlspecialchars(strtoupper($code)) ?></option><?php endforeach ?>
    </select>
</form>
