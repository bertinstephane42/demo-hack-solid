<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0">
            <div class="card-body p-4">
                <h1 class="h4 fw-bold mb-1" style="color:#003878;">Connexion administrateur</h1>
                <p class="text-muted small mb-4">Accès réservé — module « Envoi des mails ».</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ((int) ($throttle_left ?? 0) > 0): ?>
                    <div class="alert alert-warning py-2">
                        Veuillez patienter <strong><?= (int) $throttle_left ?> s</strong> avant de réessayer.
                    </div>
                <?php endif; ?>

                <form action="<?= route('admin.login') ?>" method="POST" autocomplete="off">
                    <input type="hidden" name="_csrftoken" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="mb-3">
                        <label for="emailInput" class="form-label">Adresse email</label>
                        <input type="email" name="email" id="emailInput" class="form-control"
                               required maxlength="254" autocomplete="username">
                    </div>
                    <div class="mb-4">
                        <label for="passInput" class="form-label">Mot de passe</label>
                        <input type="password" name="password" id="passInput" class="form-control"
                               required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold"
                            <?= (int) ($throttle_left ?? 0) > 0 ? 'disabled' : '' ?>>
                        Se connecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>