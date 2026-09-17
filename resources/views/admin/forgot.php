<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card border-0 shadow-lg mt-5">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold" style="color:#003878;">Mot de passe oublié</h1>
                    <p class="text-muted small mb-0">
                        Un code de sécurité à <?= \App\Services\PasswordReset::CODE_LENGTH ?> chiffres sera envoyé à
                        l'adresse d'expédition configurée pour le site. Il sera valable
                        <?= (int) (\App\Services\PasswordReset::CODE_TTL / 60) ?> minutes.
                    </p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (!empty($has_pending)): ?>
                    <div class="alert alert-info" role="alert">
                        Une demande est déjà en cours. Un code valable encore
                        <?= (int) ceil($seconds_left / 60) ?> minute<?= $seconds_left > 60 ? 's' : '' ?> a été envoyé.
                        <a href="<?= route('admin.reset') ?>" class="alert-link">Saisir le code</a>.
                    </div>
                <?php endif; ?>

                <form action="<?= route('admin.forgot') ?>" method="POST">
                    <?= csrf_field() ?>
                    <p class="mb-3">
                        <button type="submit" class="btn btn-primary fw-bold w-100 py-2"
                            <?= !empty($resend_left) ? 'disabled' : '' ?>>
                            <?= !empty($has_pending) ? 'Renvoyer un code' : 'Envoyer un code de réinitialisation' ?>
                        </button>
                    </p>
                    <?php if (!empty($resend_left)): ?>
                        <p class="text-muted small text-center mb-3">
                            Nouvel envoi possible dans <?= (int) $resend_left ?> seconde<?= $resend_left > 1 ? 's' : '' ?>.
                        </p>
                    <?php endif; ?>
                </form>

                <p class="text-center mb-0">
                    <a href="<?= route('admin.login') ?>" class="small" style="color:#003878;">Retour à la connexion</a>
                </p>
            </div>
        </div>
    </div>
</div>
