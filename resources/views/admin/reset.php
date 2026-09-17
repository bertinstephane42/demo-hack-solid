<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="h4 fw-bold mb-4" style="color:#003878;">Réinitialisation du mot de passe</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (empty($has_pending)): ?>
                    <p class="mb-3">Aucun code de réinitialisation n'est en cours de validité.</p>
                    <a href="<?= route('admin.forgot') ?>" class="btn btn-primary fw-bold">Demander un nouveau code</a>
                <?php else: ?>
                    <p class="text-muted small mb-4">
                        Saisissez le code à <?= (int) $code_length ?> chiffres reçu par e-mail.
                        Il expire dans <?= (int) ceil($seconds_left / 60) ?> minute<?= $seconds_left > 60 ? 's' : '' ?>.
                    </p>
                    <form action="<?= route('admin.reset') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <input type="text" name="code" class="form-control" id="codeInput"
                                   placeholder="Code" required inputmode="numeric" pattern="\d{<?= (int) $code_length ?>}"
                                   maxlength="<?= (int) $code_length ?>" autocomplete="one-time-code">
                            <label for="codeInput">Code reçu par e-mail</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" name="new_password" class="form-control" id="newPassword"
                                   placeholder="Nouveau mot de passe" required
                                   minlength="<?= \App\Services\Auth::PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                            <label for="newPassword">Nouveau mot de passe</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" name="confirm_password" class="form-control" id="confirmPassword"
                                   placeholder="Confirmer le mot de passe" required
                                   minlength="<?= \App\Services\Auth::PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                            <label for="confirmPassword">Confirmer le mot de passe</label>
                        </div>
                        <p class="text-muted small mb-4">
                            Au moins <?= \App\Services\Auth::PASSWORD_MIN_LENGTH ?> caractères, avec au moins une
                            minuscule, une majuscule, un chiffre et un caractère spécial parmi
                            <code><?= htmlspecialchars(\App\Services\Auth::PASSWORD_SPECIALS) ?></code>.
                        </p>
                        <button type="submit" class="btn btn-primary fw-bold">Réinitialiser le mot de passe</button>
                        <a href="<?= route('admin.forgot') ?>" class="btn btn-outline-secondary ms-2">Renvoyer un code</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
