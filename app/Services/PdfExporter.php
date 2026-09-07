<?php
namespace App\Services;

use RuntimeException;

final class PdfExporter
{
    public static function download(string $html, string $title): never
    {
        $root = dirname(__DIR__, 2);
        $directory = $root . '/storage/cache/pdf';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Le dossier temporaire PDF est indisponible.');
        }

        $token = bin2hex(random_bytes(16));
        $htmlPath = $directory . '/' . $token . '.html';
        $pdfPath = $directory . '/' . $token . '.pdf';
        $fileBase = 'file://' . $root;
        $html = str_replace(['src="/public/', "src='/public/"], ['src="'.$fileBase.'/public/', "src='".$fileBase.'/public/'], $html);
        $html = str_replace(['href="/public/', "href='/public/"], ['href="'.$fileBase.'/public/', "href='".$fileBase.'/public/'], $html);
        if (file_put_contents($htmlPath, $html, LOCK_EX) === false) {
            throw new RuntimeException('Impossible de préparer le document PDF.');
        }

        $command = [
            '/usr/bin/google-chrome', '--headless', '--no-sandbox', '--disable-gpu',
            '--allow-file-access-from-files', '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=3000', '--no-pdf-header-footer',
            '--print-to-pdf=' . $pdfPath, 'file://' . $htmlPath,
        ];
        $pipes = [];
        $process = proc_open($command, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, $directory);
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
            error_log('Export PDF Chrome: ' . trim($errors));
            throw new RuntimeException('La génération du PDF a échoué.');
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: 'document';
        $filename = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $ascii), '-')) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdfPath));
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        readfile($pdfPath);
        @unlink($pdfPath);
        exit;
    }
}
