<?php

// --- Détection dynamique du basePath ---
// La position réelle du contrôleur frontal sert de référence. On n'utilise
// SCRIPT_NAME que lorsqu'il désigne réellement le fichier (le serveur intégré
// php -S reflète l'URI demandé dans SCRIPT_NAME, ce qui fausserait la
// détection d'un chemin comme /admin/dashboard).
$scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$uri = $_SERVER['REQUEST_URI'] ?? '/';

$publicPath = '';
$scriptBase = basename($scriptFilename !== '' ? $scriptFilename : 'index.php');

if ($scriptFilename !== '' && $documentRoot !== '') {
    $root = rtrim($documentRoot, '/');
    if (str_starts_with($scriptFilename, $root . '/')) {
        $dir = dirname(substr($scriptFilename, strlen($root)));
        if ($dir !== '/' && $dir !== '' && $dir !== '.') {
            $publicPath = $dir;
        }
    }
}

if ($publicPath === '' && str_ends_with($scriptName, '/' . $scriptBase)) {
    $dir = dirname($scriptName);
    if ($dir !== '/' && $dir !== '.' && $dir !== '') {
        $publicPath = $dir;
    }
}

// Normaliser l'URI pour le routing
if ($publicPath !== '') {
    if (str_starts_with($uri, $publicPath . '/')) {
        $uri = substr($uri, strlen($publicPath));
    } elseif ($uri === $publicPath || str_ends_with($uri, 'index.php')) {
        $uri = '/';
    }
}

// Strip query string pour le routing
$queryPos = strpos($uri, '?');
if ($queryPos !== false) {
    $uri = substr($uri, 0, $queryPos);
}

if ($uri === '' || $uri === 'index.php') {
    $uri = '/';
}

// Stocker le basePath pour les helpers et le JS
$_SERVER['REQUEST_URI'] = $uri;
$_SERVER['APP_BASE_PATH'] = $publicPath;

require __DIR__ . '/../bootstrap/app.php';

$app->setPublicPath($publicPath);

$app->run();
