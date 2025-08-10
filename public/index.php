<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

try {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
} catch (\Exception $e) {
    $_SERVER['APP_ENV'] = 'dev';
    $_SERVER['APP_DEBUG'] = true;
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

try {
    $kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (\Throwable $e) {
    header('HTTP/1.1 500 Internal Server Error');
    if ($_SERVER['APP_DEBUG']) {
        echo '<html><body><h1>Erreur 500</h1>';
        echo '<p>Message: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Fichier: ' . htmlspecialchars($e->getFile()) . ' (ligne ' . $e->getLine() . ')</p>';
        echo '<h2>Trace:</h2>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    } else {
        echo 'Une erreur interne est survenue. Veuillez contacter l\'administrateur.';
    }
}
