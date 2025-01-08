<?php

declare(strict_types=1);

namespace App\Core\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    private static ?Environment $twig = null;

    public static function make(string $template, array $data = []): string
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . "/../../Views");
            self::$twig = new Environment($loader, [
                'auto_reload' => true,
                'debug' => true,
            ]);
        }

        return self::$twig->render($template . '.twig', $data);
    }
}