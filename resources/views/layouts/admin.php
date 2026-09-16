<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $title ?? 'Administration — Cours-Réseaux' ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/spacelab/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: #f4f7fb; }
        .card { box-shadow: 0 4px 16px rgba(10,29,66,.08); }
    </style>
</head>
<body class="d-flex flex-column" style="min-height:100vh;">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background:linear-gradient(90deg,#1a2b4c,#2f6fd6);">
        <div class="container">
            <span class="navbar-brand fw-bold">Cours-Réseaux — Administration</span>
            <div class="d-flex gap-2">
                <a href="<?= route('home') ?>" class="btn btn-outline-light btn-sm">Voir le site</a>
                <?php if (!empty($admin_email)): ?>
                    <a href="<?= route('admin.logout') ?>" class="btn btn-outline-light btn-sm"
                       onclick="return confirm('Déconnexion ?');">Déconnexion</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container my-4 flex-grow-1">
        <?= $content ?? '' ?>
    </main>
    <footer class="bg-white py-3 border-top">
        <div class="container d-flex justify-content-between align-items-center">
            <small class="text-muted">&copy; <?= $year ?? date('Y') ?> — Ressources pédagogiques informatiques</small>
            <small class="text-muted">Espace privé — accès restreint</small>
        </div>
    </footer>
</body>
</html>