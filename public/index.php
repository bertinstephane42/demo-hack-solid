<?php

// --- Détection dynamique du basePath ---
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$scriptDir = dirname($scriptName);
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Normaliser l'URI pour le routing
if ($scriptDir !== '/' && $scriptDir !== '.') {
    if (str_starts_with($uri, $scriptDir . '/')) {
        $uri = substr($uri, strlen($scriptDir));
    } elseif ($uri === $scriptDir || str_ends_with($uri, 'index.php')) {
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
$publicPath = ($scriptDir !== '/' && $scriptDir !== '.') ? $scriptDir : '';
$_SERVER['REQUEST_URI'] = $uri;
$_SERVER['APP_BASE_PATH'] = $publicPath;

require __DIR__ . '/../bootstrap/app.php';

$app->setPublicPath($publicPath);

$app->run();
