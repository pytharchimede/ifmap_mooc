<?php
namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
        $content = ob_get_clean();
        require dirname(__DIR__, 2) . '/resources/views/layouts/' . $layout . '.php';
    }
}

