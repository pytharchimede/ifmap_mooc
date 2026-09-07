<?php
namespace App\Services;

use RuntimeException;

final class PdfExporter
{
    public static function download(string $html, string $title): never
    {
        $root = dirname(__DIR__, 2);
        $filename = self::filename($title);
        $html = self::prepareForPdf($html);

        if (class_exists(\Dompdf\Dompdf::class)) {
            self::downloadWithDompdf($html, $filename, $root);
        }

        $browser = self::findBrowser();
        if ($browser !== null && function_exists('proc_open')) {
            self::downloadWithBrowser($html, $filename, $root, $browser);
        }

        throw new RuntimeException('Aucun moteur PDF compatible n’est disponible. Installez les dépendances Composer (dompdf/dompdf) sur le serveur.');
    }

    private static function prepareForPdf(string $html): string
    {
        // Les commandes de l'interface ne doivent jamais faire partie du document PDF.
        $html = preg_replace('#<div\s+class="document-tools"[^>]*>.*?</div>#si', '', $html) ?? $html;

        if (!str_contains($html, 'official-document')) {
            return $html;
        }

        // Le diplôme doit occuper exactement une feuille A4 paysage, sans marge navigateur.
        $pdfCss = <<<'CSS'
<style id="ifmap-pdf-export">
@page { size: A4 landscape; margin: 0; }
html, body {
    width: 297mm !important;
    height: 210mm !important;
    min-width: 297mm !important;
    min-height: 210mm !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
    background: #fff !important;
}
body { display: block !important; }
.document-tools { display: none !important; }
.official-document {
    box-sizing: border-box !important;
    width: 297mm !important;
    height: 210mm !important;
    min-width: 297mm !important;
    min-height: 210mm !important;
    max-width: 297mm !important;
    max-height: 210mm !important;
    margin: 0 !important;
    box-shadow: none !important;
    overflow: hidden !important;
    transform: none !important;
    page-break-before: avoid !important;
    page-break-after: avoid !important;
    page-break-inside: avoid !important;
}
</style>
CSS;

        return str_replace('</head>', $pdfCss . '</head>', $html);
    }

    private static function downloadWithDompdf(string $html, string $filename, string $root): never
    {
        try {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('defaultMediaType', 'print');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('chroot', $root);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->setBasePath($root);
            $dompdf->loadHtml(self::normalizeAssets($html, $root), 'UTF-8');
            $landscape = str_contains($html, 'official-document') || str_contains($html, 'size:A4 landscape');
            $dompdf->setPaper('A4', $landscape ? 'landscape' : 'portrait');
            $dompdf->render();

            $pdf = $dompdf->output();
            if (!is_string($pdf) || strlen($pdf) < 1000) {
                throw new RuntimeException('Le moteur PDF a retourné un document vide.');
            }
            self::send($pdf, $filename);
        } catch (\Throwable $e) {
            error_log('Export PDF Dompdf: ' . $e->getMessage());
            throw new RuntimeException('La génération du PDF a échoué.', 0, $e);
        }
    }

    private static function downloadWithBrowser(string $html, string $filename, string $root, string $browser): never
    {
        $directory = $root . '/storage/cache/pdf';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Le dossier temporaire PDF est indisponible.');
        }

        $token = bin2hex(random_bytes(16));
        $htmlPath = $directory . '/' . $token . '.html';
        $pdfPath = $directory . '/' . $token . '.pdf';
        $html = self::normalizeAssets($html, $root);

        if (file_put_contents($htmlPath, $html, LOCK_EX) === false) {
            throw new RuntimeException('Impossible de préparer le document PDF.');
        }

        $command = [
            $browser, '--headless', '--no-sandbox', '--disable-gpu',
            '--allow-file-access-from-files', '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=3000', '--no-pdf-header-footer',
            '--print-to-pdf=' . $pdfPath, 'file://' . $htmlPath,
        ];
        $pipes = [];
        $process = @proc_open($command, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, $directory);
        if (!is_resource($process)) {
            @unlink($htmlPath);
            throw new RuntimeException('Le moteur PDF ne peut pas démarrer.');
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]); fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exitCode = proc_close($process);
        @unlink($htmlPath);

        if ($exitCode !== 0 || !is_file($pdfPath) || filesize($pdfPath) < 1000) {
            @unlink($pdfPath);
            error_log('Export PDF navigateur: ' . trim($errors));
            throw new RuntimeException('La génération du PDF a échoué.');
        }

        $pdf = file_get_contents($pdfPath);
        @unlink($pdfPath);
        if ($pdf === false) throw new RuntimeException('Impossible de lire le PDF généré.');
        self::send($pdf, $filename);
    }

    private static function normalizeAssets(string $html, string $root): string
    {
        $fileBase = 'file://' . $root;
        return str_replace(
            ['src="/public/', "src='/public/", 'href="/public/', "href='/public/"],
            ['src="'.$fileBase.'/public/', "src='".$fileBase.'/public/', 'href="'.$fileBase.'/public/', "href='".$fileBase.'/public/'],
            $html
        );
    }

    private static function findBrowser(): ?string
    {
        foreach (['/usr/bin/google-chrome','/usr/bin/google-chrome-stable','/usr/bin/chromium','/usr/bin/chromium-browser','/snap/bin/chromium'] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) return $candidate;
        }
        return null;
    }

    private static function filename(string $title): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: 'document';
        return (strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $ascii), '-')) ?: 'document') . '.pdf';
    }

    private static function send(string $pdf, string $filename): never
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
        exit;
    }
}
