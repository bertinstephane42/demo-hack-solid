<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="h4 fw-bold mb-4" style="color:#003878;">Sauvegarde de la configuration</h2>

        <?php if ((int) $checks_fail > 0): ?>
            <div class="alert alert-warning" role="alert">
                <?= (int) $checks_fail ?> contrôle<?= $checks_fail > 1 ? 's' : '' ?> du système en échec.
                <a href="<?= route('admin.system') ?>" class="alert-link">Vérifier les droits d'écriture</a>.
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h3 class="h6 fw-bold mb-2" style="color:#003878;">Sauvegarde complète</h3>
                <p class="text-muted small mb-3">
                    Exporte au format JSON la configuration du site : application, compte administrateur,
                    envoi des mails, API, limitation de débit et sécurité. Les secrets (mot de passe
                    administrateur, mot de passe SMTP et jeton d'API) sont retirés de l'export.
                </p>
                <form action="<?= route('admin.export.download') ?>" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary fw-bold">Télécharger la sauvegarde complète</button>
                </form>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Le fichier téléchargé peut être conservé hors ligne. Aucune donnée n'est envoyée à un service tiers.
        </p>
    </div>
</div>
