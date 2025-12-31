<?php

use App\Kernel;

// --- FIX : Support HTTPS pour Ngrok / Dev uniquement ---
// On ne force HTTPS que si on est sur ngrok et que le header est présent
if (
    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
    $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' &&
    isset($_SERVER['HTTP_HOST']) &&
    (str_contains($_SERVER['HTTP_HOST'], 'ngrok-free') || str_contains($_SERVER['HTTP_HOST'], 'localhost'))
) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
