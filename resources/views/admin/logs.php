<div class="row justify-content-center">
    <div class="col-lg-10">
        <h2 class="h4 fw-bold mb-4" style="color:#003878;">Journal de connexion</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <div class="fs-4 fw-bold text-success"><?= (int) $count_success ?></div>
                        <div class="text-muted small">Connexions réussies</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <div class="fs-4 fw-bold text-danger"><?= (int) $count_fail ?></div>
                        <div class="text-muted small">Échecs / blocages</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <div class="fs-4 fw-bold" style="color:#003878;"><?= count($entries) ?></div>
                        <div class="text-muted small">Lignes affichées (max 200)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (empty($entries)): ?>
                    <p class="text-muted mb-0">Aucune entrée pour le moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Résultat</th>
                                    <th>Adresse IP</th>
                                    <th>E-mail</th>
                                    <th>Détail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $labels = [
                                    'success' => ['Succès', 'success'],
                                    'fail' => ['Échec', 'danger'],
                                    'throttled' => ['Trop de tentatives', 'warning'],
                                    'csrf' => ['Jeton CSRF invalide', 'warning'],
                                    'timeout' => ['Session expirée', 'secondary'],
                                    'reset-request' => ['Demande de code', 'info'],
                                    'reset-fail' => ['Échec réinitialisation', 'danger'],
                                    'reset-success' => ['Réinitialisation OK', 'success'],
                                ];
                                ?>
                                <?php foreach ($entries as $entry): ?>
                                    <?php $label = $labels[$entry['result']] ?? ['Inconnu', 'secondary']; ?>
                                    <tr>
                                        <td class="text-nowrap small"><?= htmlspecialchars($entry['time'] !== '' ? $entry['time'] : '—') ?></td>
                                        <td><span class="badge bg-<?= $label[1] ?>"><?= htmlspecialchars($label[0]) ?></span></td>
                                        <td class="small"><?= htmlspecialchars($entry['ip'] !== '' ? $entry['ip'] : '—') ?></td>
                                        <td class="small"><?= htmlspecialchars($entry['email'] !== '' ? $entry['email'] : '—') ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($entry['extra'] !== '' ? $entry['extra'] : '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <form action="<?= route('admin.logs.clear') ?>" method="POST" class="mt-3"
                          onsubmit="return confirm('Vider définitivement le journal de connexion ?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">Vider le journal</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Emplacement : <code>storage/logs/login.log</code> (dossier protégé, inaccessible depuis le web).
        </p>
    </div>
</div>
