<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="h4 fw-bold mb-4" style="color:#003878;">Changer le mot de passe</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="<?= route('admin.password') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-floating mb-3">
                        <input type="password" name="current_password" class="form-control" id="currentPassword"
                               placeholder="Mot de passe actuel" required autocomplete="current-password">
                        <label for="currentPassword">Mot de passe actuel</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="new_password" class="form-control" id="newPassword"
                               placeholder="Nouveau mot de passe" required minlength="20" autocomplete="new-password">
                        <label for="newPassword">Nouveau mot de passe</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="confirm_password" class="form-control" id="confirmPassword"
                               placeholder="Confirmer le mot de passe" required minlength="20" autocomplete="new-password">
                        <label for="confirmPassword">Confirmer le mot de passe</label>
                    </div>
                    <p class="text-muted small mb-4">
                        Au moins 20 caractères, avec au moins une minuscule, une majuscule, un chiffre
                        et un caractère spécial parmi
                        <code><?= htmlspecialchars(\App\Services\Auth::PASSWORD_SPECIALS) ?></code>.
                    </p>
                    <button type="submit" class="btn btn-primary fw-bold">Modifier le mot de passe</button>
                    <a href="<?= route('admin.dashboard') ?>" class="btn btn-outline-secondary ms-2">Retour</a>
                </form>
            </div>
        </div>
    </div>
</div>