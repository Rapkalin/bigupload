<?php

if (!function_exists("formatBytes")) {
    function formatBytes(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return sprintf("%.{$decimals}f", $bytes / 1024 ** $factor) . $units[$factor];
    }
}

if (!function_exists("getDomaineUrl")) {
    function getDomaineUrl(): string
    {
        return match ($_ENV['APP_ENV'] ?? null) {
            'preprod' => $_ENV['APP_DOMAINE_PREPROD'],
            'prod' => $_ENV['APP_DOMAINE_PROD'],
            default => $_ENV['APP_DOMAINE_LOCAL'],
        };
    }
}
