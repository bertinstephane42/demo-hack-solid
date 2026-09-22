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
                <form action="<?= route('admin.export.download') ?>" method="POST" id="backupDownloadForm">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-primary fw-bold"
                            data-bs-toggle="modal" data-bs-target="#backupConfirmModal">
                        Télécharger la sauvegarde complète
                    </button>
                </form>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Le fichier téléchargé peut être conservé hors ligne. Aucune donnée n'est envoyée à un service tiers.
        </p>
    </div>
</div>

<div class="modal fade" id="backupConfirmModal" tabindex="-1" aria-labelledby="backupConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="backupConfirmModalLabel" style="color:#003878;">Télécharger la sauvegarde complète ?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-2">La sauvegarde exporte au format JSON :</p>
                <ul class="small text-muted ps-3 mb-3">
                    <li>l'application et la configuration associée ;</li>
                    <li>le compte administrateur, l'envoi des mails, l'API, la limitation de débit et la sécurité.</li>
                </ul>
                <p class="small mb-0">
                    Les secrets (mot de passe administrateur, mots de passe SMTP, jetons d'API) sont retirés de l'export.
                    Aucune donnée n'est envoyée à un service tiers.
                    Souhaitez-vous télécharger ce fichier ?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="backupDownloadForm" class="btn btn-primary fw-bold">Télécharger la sauvegarde</button>
            </div>
        </div>
    </div>
</div>
