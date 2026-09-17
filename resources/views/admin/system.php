<div class="row justify-content-center">
    <div class="col-lg-10">
        <h2 class="h4 fw-bold mb-4" style="color:#003878;">Contrôle du système</h2>

        <div class="alert <?= $summary['ready'] ? 'alert-success' : 'alert-warning' ?>" role="alert">
            <?php if ($summary['ready']): ?>
                Tous les contrôles sont au vert (<?= (int) $summary['ok'] ?>/<?= (int) $summary['total'] ?>).
                Les enregistrements depuis l'administration peuvent être effectués.
            <?php else: ?>
                <?= (int) $summary['fail'] ?> contrôle<?= $summary['fail'] > 1 ? 's' : '' ?> en échec sur
                <?= (int) $summary['total'] ?>. Corrigez les droits d'écriture ci-dessous, sans quoi certaines
                modifications risquent de ne pas être enregistrées.
            <?php endif; ?>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h3 class="h6 fw-bold mb-3" style="color:#003878;">Fichiers temporaires (storage/tmp)</h3>
                <p class="text-muted small mb-3">
                    Compteurs de limitation de débit (<code>rate_*.json</code>) et quota d'envoi
                    (<code>quota_mail.json</code>). La purge les réinitialise ; les fichiers sont recréés
                    automatiquement au fil des visites. Les fichiers cachés, les verrous
                    (<code>.lock</code>) et les demandes de réinitialisation en cours ne sont jamais supprimés.
                    <?php if ((int) $tmp_count > 0): ?>
                        <br><strong><?= (int) $tmp_count ?></strong> fichier<?= $tmp_count > 1 ? 's' : '' ?>
                        à purger (<?= htmlspecialchars($tmp_size) ?>).
                    <?php else: ?>
                        <br>Aucun fichier temporaire actuellement.
                    <?php endif; ?>
                </p>
                <?php if ((int) $tmp_count > 0): ?>
                    <details class="mb-3">
                        <summary class="text-muted small" style="cursor:pointer;">Voir les fichiers concernés</summary>
                        <ul class="text-muted small mb-0 mt-2">
                            <?php foreach ($tmp_files as $tmpFile): ?>
                                <li><?= htmlspecialchars($tmpFile) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                    <form action="<?= route('admin.system.purge-tmp') ?>" method="POST"
                          onsubmit="return confirm('Purger tous les fichiers temporaires de storage/tmp ?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">Purger les fichiers temporaires</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php foreach ($groups as $group => $items): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold mb-3" style="color:#003878;"><?= htmlspecialchars($group) ?></h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40%;">Élément</th>
                                    <th style="width:20%;">État</th>
                                    <th>Détail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="me-2"><?= $item['ok'] ? '&#9989;' : '&#10060;' ?></span>
                                            <?= htmlspecialchars($item['label']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $item['ok'] ? 'success' : 'danger' ?>">
                                                <?= htmlspecialchars($item['value']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($item['hint']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
