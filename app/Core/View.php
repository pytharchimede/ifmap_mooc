<?php
namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $brand = ['name'=>'IFMAP Learning','primary'=>'#5547e8','accent'=>'#f59e0b','logo'=>null,'favicon'=>null,'signature'=>null];
        try {
            foreach (Database::connection()->query("SELECT `key`,`value` FROM settings WHERE `group`='branding'") as $row) {
                if (array_key_exists($row['key'], $brand)) $brand[$row['key']] = $row['value'];
            }
        } catch (\Throwable) {
            $brand = array_merge($brand, $_SESSION['brand'] ?? []);
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $basePath = rtrim(str_replace('/index.php', '', $script), '/');
        $assetUrl = static function (?string $path) use ($basePath): ?string {
            if ($path === null || $path === '') return $path;
            if (preg_match('~^(?:https?:)?//~i', $path) || str_starts_with($path, 'data:')) return $path;
            if ($basePath !== '' && str_starts_with($path, $basePath . '/')) return $path;
            if (str_starts_with($path, '/')) return $basePath . $path;
            return $basePath . '/' . ltrim($path, '/');
        };

        foreach (['logo','favicon','signature'] as $assetKey) {
            $brand[$assetKey] = $assetUrl($brand[$assetKey] ?? null);
        }

        $brand = array_merge($brand, $data['brand'] ?? []);
        foreach (['logo','favicon','signature'] as $assetKey) {
            $brand[$assetKey] = $assetUrl($brand[$assetKey] ?? null);
        }

        $_SESSION['brand'] = $brand;
        $data['brand'] = $brand;
        extract($data, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
        $content = ob_get_clean();

        if ($view === 'academy/certificate' && !empty($brand['signature']) && empty($isSpecimen)) {
            $image = '<img class="digital-signature" style="width:160px;height:58px;object-fit:contain;margin:0 auto -7px;position:relative;z-index:1" src="'.htmlspecialchars($brand['signature'], ENT_QUOTES, 'UTF-8').'" alt="Signature de la Direction">';
            $content = str_replace('<div class="signature"><i></i>', '<div class="signature">'.$image.'<i></i>', $content);
        }

        if (in_array($layout, ['site','app'], true)) {
            $content = '<span hidden data-global-brand data-name="'.htmlspecialchars($brand['name'], ENT_QUOTES, 'UTF-8').'" data-logo="'.htmlspecialchars((string)$brand['logo'], ENT_QUOTES, 'UTF-8').'"></span>'.$content;
        }

        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/layouts/' . $layout . '.php';
        $page = ob_get_clean();

        if (in_array($layout, ['site','app','admin','auth','customer-auth'], true)) {
            $favicon = $brand['favicon'] ?: $brand['logo'];
            if (!empty($favicon)) {
                $tag = '<link rel="icon" href="'.htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8').'">';
                $page = str_replace('</head>', $tag.'</head>', $page);
            }
        }

        if ($layout === 'site') {
            $mark = !empty($brand['logo']) ? '<img src="'.htmlspecialchars($brand['logo'], ENT_QUOTES, 'UTF-8').'" alt="" style="width:100%;height:100%;object-fit:contain">' : 'IF';
            $siteLogo = '<span>'.$mark.'</span><strong>'.htmlspecialchars($brand['name'], ENT_QUOTES, 'UTF-8').'<small>Institut de Formation aux Métiers</small></strong>';
            $page = str_replace('<span>IF</span><strong>IFMAP<small>Institut de Formation aux Métiers</small></strong>', $siteLogo, $page);
            $page = str_replace(' — IFMAP</title>', ' — '.htmlspecialchars($brand['name'], ENT_QUOTES, 'UTF-8').'</title>', $page);
        }

        if (in_array($layout, ['document','certificate'], true)) {
            if (($_GET['download'] ?? '') === 'pdf') {
                \App\Services\PdfExporter::download($page, ($title ?: 'document') . '-' . date('Y-m-d'));
            }
            $query = $_GET;
            $query['download'] = 'pdf';
            $downloadUrl = (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '') . '?' . http_build_query($query);
            $downloadLink = '<a href="'.htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8').'">↓ Télécharger le PDF A4</a>';
            $page = preg_replace('#<button onclick="window\.print\(\)">.*?</button>#u', $downloadLink, $page, 1) ?? $page;
        }

        echo $page;
    }
}
