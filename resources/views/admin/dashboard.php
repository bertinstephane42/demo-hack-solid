<div class="row justify-content-center">
    <div class="col-md-9">
        <h2 class="h4 fw-bold mb-4" style="color:#003878;">Tableau de bord</h2>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <h5 class="fw-bold text-uppercase text-muted small mb-3">Modules disponibles</h5>
        <div class="row g-4 mb-4">
            <?php foreach ($modules as $key => $module): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <div class="display-6 mb-2"><?= $module['icon'] ?></div>
                            <h5 class="card-title fw-bold" style="color:#003878;"><?= htmlspecialchars($module['title']) ?></h5>
                            <p class="card-text text-muted small"><?= htmlspecialchars($module['description']) ?></p>
                            <p class="card-text">
                                <span class="badge <?= htmlspecialchars($module['status_class']) ?>">
                                    <?= htmlspecialchars($module['status']) ?>
                                </span>
                            </p>
                            <a href="<?= $module['route'] ?>" class="btn btn-outline-primary btn-sm">Configurer</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h5 class="fw-bold text-uppercase text-muted small mb-3">Maintenance</h5>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="<?= route('admin.system') ?>" class="btn btn-outline-primary btn-sm">Système</a>
                    <a href="<?= route('admin.logs') ?>" class="btn btn-outline-primary btn-sm">Journal de connexion</a>
                    <a href="<?= route('admin.export') ?>" class="btn btn-outline-primary btn-sm">Sauvegarde</a>
                    <form action="<?= route('admin.system.purge-tmp') ?>" method="POST" class="d-inline"
                          onsubmit="return confirm('Purger tous les fichiers temporaires de storage/tmp ?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            Purger storage/tmp<?= (int) $tmp_count > 0 ? ' (' . (int) $tmp_count . ')' : '' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3" style="color:#003878;">Informations du compte</h5>
                <p class="text-muted mb-1"><strong>Email :</strong> <?= htmlspecialchars($admin_email) ?></p>
                <p class="text-muted mb-0"><strong>Connecté depuis :</strong> <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
    </div>
</div>