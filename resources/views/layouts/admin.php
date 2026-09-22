<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $title ?? 'Administration — Cours-Réseaux' ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/flatly/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #f4f7fb;
        }
        .card {
            box-shadow: 0 4px 16px rgba(10,29,66,.08);
        }
        .navbar-dark {
            background: linear-gradient(90deg, #1a2b4c, #2f6fd6) !important;
        }
    </style>
</head>
<body class="d-flex flex-column" style="min-height:100vh;">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container d-flex align-items-center justify-content-between">
            <span class="navbar-brand fw-bold">
                <span class="d-none d-md-inline">Cours-Réseaux — </span>Administration
            </span>
            <div class="d-flex gap-2">
                <a href="<?= route('home') ?>" class="btn btn-outline-light btn-sm">Site public</a>
                <?php if (app(\App\Services\Auth::class)->check()): ?>
                    <a href="<?= route('admin.dashboard') ?>" class="btn btn-outline-warning btn-sm">Dashboard</a>
                    <a href="<?= route('admin.mail') ?>" class="btn btn-outline-light btn-sm">Mails</a>
                    <a href="<?= route('admin.password') ?>" class="btn btn-outline-light btn-sm">Mot de passe</a>
                    <a href="<?= route('admin.user') ?>" class="btn btn-outline-light btn-sm">Compte</a>
                    <a href="<?= route('admin.logs') ?>" class="btn btn-outline-light btn-sm">Journaux</a>
                    <a href="<?= route('admin.export') ?>" class="btn btn-outline-light btn-sm">Sauvegarde</a>
                    <a href="<?= route('admin.system') ?>" class="btn btn-outline-light btn-sm">Système</a>
                    <form action="<?= route('admin.logout') ?>" method="POST" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">Déconnexion</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="flex-grow-1 py-4">
        <div class="container">
            <?= $content ?? '' ?>
        </div>
    </main>
    <footer class="bg-white mt-auto py-3 border-top">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">
                <strong style="color:#003878;">Cours-Réseaux</strong> — Administration
            </small>
            <small class="text-muted">
                &copy; <?= $year ?? date('Y') ?> — Espace privé, accès restreint
            </small>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.alert').forEach(function (alert) {
            setTimeout(function () {
                if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                } else {
                    alert.remove();
                }
            }, 6000);
        });
    });
    </script>
    <div class="modal fade" id="appConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="appConfirmTitle" style="color:#003878;">Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="appConfirmMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger fw-bold" id="appConfirmOk">Confirmer</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function () {
        'use strict';
        var modalEl = document.getElementById('appConfirmModal');
        if (!modalEl || typeof bootstrap === 'undefined') {
            return;
        }
        var titleEl = document.getElementById('appConfirmTitle');
        var msgEl = document.getElementById('appConfirmMessage');
        var okBtn = document.getElementById('appConfirmOk');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var currentAction = null;

        window.confirmAction = function (message, onConfirm, options) {
            options = options || {};
            if (titleEl) {
                titleEl.textContent = options.title || 'Confirmation';
            }
            msgEl.textContent = message;
            okBtn.textContent = options.okLabel || 'Confirmer';
            okBtn.className = 'btn fw-bold ' + (options.tone === 'primary' ? 'btn-primary' : 'btn-danger');
            okBtn.setAttribute('aria-label', okBtn.textContent);
            currentAction = onConfirm;
            modal.show();
        };

        window.alertMessage = function (message, title) {
            if (titleEl) {
                titleEl.textContent = title || 'Information';
            }
            msgEl.textContent = message;
            okBtn.textContent = 'Fermer';
            okBtn.className = 'btn btn-primary fw-bold';
            okBtn.setAttribute('aria-label', 'Fermer');
            currentAction = null;
            modal.show();
        };

        okBtn.addEventListener('click', function () {
            var action = currentAction;
            currentAction = null;
            modal.hide();
            if (typeof action === 'function') {
                action();
            }
        });

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof Element) || !form.hasAttribute('data-confirm')) {
                return;
            }
            event.preventDefault();
            confirmAction(form.getAttribute('data-confirm'), function () {
                form.submit();
            }, { tone: form.getAttribute('data-confirm-tone') || 'danger' });
        }, true);
    })();
    </script>
</body>
</html>