<?php
declare(strict_types=1);
namespace App;
final class Env {
    public static function load(string $path): void {
        if (!is_file($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key,$value] = array_map('trim', explode('=', $line, 2));
            if ($key === '') continue;
            if ((str_starts_with($value,'"') && str_ends_with($value,'"')) || (str_starts_with($value,"'") && str_ends_with($value,"'"))) {
                $value = substr($value,1,-1);
            }
            $value = str_replace(['\\n','\\r','\\t'], ["\n","\r","\t"], $value);
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
