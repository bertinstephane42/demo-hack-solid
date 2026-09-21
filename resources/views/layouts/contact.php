<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Contact — Cours-Réseaux' ?></title>
    <meta name="description" content="Contactez l'équipe Cours-Réseaux pour toute question relative aux formations BTS SIO, CPI et Expert Cyber.">
    <link rel="canonical" href="https://cours-reseaux.fr/contact">
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/spacelab/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/index.css') ?>">
    <style>
    body {
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;
        background: linear-gradient(180deg, #eef4fa 0%, #f7faff 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    footer {
        margin-top: auto;
    }
    </style>
</head>
<body>
    @include('partials.floating-bg')
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center gap-3">
                <span class="logo-mark position-relative">CN</span>
                <div class="d-none d-md-block">
                    <div style="font-weight:700">Contactez-nous</div>
                    <small class="text-muted">Nous répondons rapidement aux étudiants et formateurs.</small>
                </div>
            </a>
            <div>
                <a href="<?= route('home') ?>" class="btn btn-outline-secondary">Retour à l'accueil</a>
            </div>
        </div>
    </nav>
    <?= $content ?? '' ?>
    <footer class="bg-light py-4 border-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span id="linkedin-name" class="linkedin-profile"><strong>Stéphane BERTIN</strong></span><br>
                <small class="text-muted">&copy; <?= $year ?? date('Y') ?> — Ressources pédagogiques informatiques</small>
            </div>
            <div class="text-end">
                <small class="text-muted">Hébergement sécurisé • CloudFlare • Proxy PHP</small>
            </div>
        </div>
    </footer>
    <script>
        (function () {
            var parts = ["s", "t", "e", "p", "h", "a", "n", "e", "-", "b", "e", "r", "t", "i", "n", "4", "2"];
            var el = document.getElementById("linkedin-name");
            if (el) {
                var username = parts.join("");
                el.innerHTML = '<a href="https://www.linkedin.com/in/' + username + '/" target="_blank" rel="noopener noreferrer"><strong>Stéphane BERTIN</strong></a>';
            }
        })();
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const alertBox = document.querySelector(".alert-success, .alert-danger");
            if (alertBox) {
                setTimeout(() => {
                    alertBox.style.transition = "opacity 0.6s ease";
                    alertBox.style.opacity = "0";
                    setTimeout(() => alertBox.remove(), 600);
                }, 10000);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/index.js') ?>"></script>
    <?php if (!empty($turnstile_sitekey)): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <script>
            (function () {
                var wrap = document.getElementById('turnstileWidget');
                if (!wrap) { return; }
                var revealed = false;
                function revealFallback() {
                    if (revealed) { return; }
                    revealed = true;
                    var fb = document.getElementById('captchaFallback');
                    var inp = document.getElementById('captchaFallbackInput');
                    if (fb) { fb.style.display = 'block'; }
                    if (inp) { inp.disabled = false; }
                }
                window.cr_turnstile_error = revealFallback;
                if (wrap.childElementCount === 0) {
                    setTimeout(function () {
                        var w = document.getElementById('turnstileWidget');
                        if (w && w.childElementCount === 0) { revealFallback(); }
                    }, 6000);
                }
                document.addEventListener('DOMContentLoaded', function () {
                    if (document.querySelector('.alert-danger') && document.getElementById('turnstileWidget')) {
                        revealFallback();
                    }
                });
            })();
        </script>
    <?php endif; ?>
</body>
</html>
