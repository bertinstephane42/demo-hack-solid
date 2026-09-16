<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card border-0 shadow-lg mt-5">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h1 class="h4 fw-bold" style="color:#003878;">Administration Cours-Réseaux</h1>
                    <p class="text-muted small">Connectez-vous pour gérer votre site</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center" id="loginError" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ((int) ($throttle_left ?? 0) > 0): ?>
                    <div class="alert alert-warning text-center py-2">
                        Veuillez patienter <strong><?= (int) $throttle_left ?> s</strong> avant de réessayer.
                    </div>
                <?php endif; ?>
                <form action="<?= route('admin.login') ?>" method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control" id="emailInput" placeholder="Email"
                               required autocomplete="username">
                        <label for="emailInput">Adresse email</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" name="password" class="form-control" id="passwordInput"
                               placeholder="Mot de passe" required autocomplete="current-password">
                        <label for="passwordInput">Mot de passe</label>
                    </div>
                    <button type="submit" class="btn btn-primary fw-bold w-100 py-2"
                            <?= (int) ($throttle_left ?? 0) > 0 ? 'disabled' : '' ?>>
                        Se connecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>