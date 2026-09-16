<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Cours-Réseaux' ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_description ?? 'Ressources pédagogiques informatiques pour BTS SIO, formations CPI et parcours Expert Cyber — TP réalistes et sécurisés.') ?>">
    <link rel="canonical" href="https://cours-reseaux.fr/">
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/spacelab/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/index.css') ?>">
</head>
<body class="d-flex flex-column">
    @include('partials.floating-bg')
    @include('partials.navbar')
    <main>
        <?= $content ?? '' ?>
    </main>
    @include('partials.modals')
    @include('partials.footer')
    <script>
        window.APP_PATH = '<?= rtrim(asset(''), '/') ?>';
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/index.js') ?>"></script>
</body>
</html>
