<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="h4 fw-bold mb-4" style="color:#003878;">Compte utilisateur</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <p class="text-muted small mb-4">
                    Adresse de connexion actuelle :
                    <strong><?= htmlspecialchars($admin_email) ?></strong>
                </p>
                <form action="<?= route('admin.user') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-floating mb-3">
                        <input type="password" name="current_password" class="form-control" id="currentPassword" placeholder="Mot de passe actuel" required autocomplete="current-password">
                        <label for="currentPassword">Mot de passe actuel</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" name="new_email" class="form-control" id="newEmail" placeholder="Nouvelle adresse e-mail" required autocomplete="off">
                        <label for="newEmail">Nouvelle adresse e-mail</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" name="confirm_email" class="form-control" id="confirmEmail" placeholder="Confirmer la nouvelle adresse" required autocomplete="off">
                        <label for="confirmEmail">Confirmer la nouvelle adresse</label>
                    </div>
                    <p class="text-muted small mb-4">
                        Saisissez votre mot de passe actuel pour autoriser le changement
                        d'adresse e-mail de connexion au panneau d'administration.
                    </p>
                    <button type="submit" class="btn btn-primary fw-bold">Modifier l'adresse e-mail</button>
                    <a href="<?= route('admin.dashboard') ?>" class="btn btn-outline-secondary ms-2">Retour</a>
                </form>
            </div>
        </div>
    </div>
</div>