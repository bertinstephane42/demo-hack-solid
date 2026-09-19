<?php
$base = route('admin.logs');
$current = $current ?? 'login';
$tabs = $tabs ?? [];
$entries = $entries ?? [];
$total = (int) ($total ?? 0);
$page = (int) ($page ?? 1);
$pages = max(1, (int) ($pages ?? 1));
$from = (int) ($from ?? 0);
$to = (int) ($to ?? 0);
$perPage = (int) ($per_page ?? 25);
?>
<div class="row justify-content-center">
    <div class="col-lg-11">
        <h2 class="h4 fw-bold mb-1" style="color:#003878;">Journaux</h2>
        <p class="text-muted small mb-4">
            Connexions à l'administration et messages envoyés depuis la page de contact,
            du plus récent au plus ancien.
            Les fichiers sont stockés dans <code>storage/logs</code> (dossier protégé, inaccessible depuis le web).
        </p>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($current === 'login'): ?>
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
                            <div class="fs-4 fw-bold" style="color:#003878;"><?= (int) $total ?></div>
                            <div class="text-muted small">Événements enregistrés</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="fs-4 fw-bold" style="color:#003878;"><?= (int) $total ?></div>
                            <div class="text-muted small">Messages enregistrés</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="fs-4 fw-bold text-success"><?= (int) $count_sent ?></div>
                            <div class="text-muted small">Mails envoyés</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body">
                            <div class="fs-4 fw-bold text-danger"><?= (int) $count_failed ?></div>
                            <div class="text-muted small">Mails en échec</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <ul class="nav nav-pills flex-wrap gap-2 mb-3">
            <?php foreach ($tabs as $key => $meta): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $key === $current ? 'active' : '' ?>"
                       href="<?= $base ?>?f=<?= urlencode($key) ?>">
                        <?= htmlspecialchars($meta['label']) ?>
                        <span class="badge bg-light text-dark ms-1"><?= (int) $meta['count'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="text-muted small mb-3">
            <?php if ($total > 0): ?>
                <?= (int) $from ?>–<?= (int) $to ?> sur <?= (int) $total ?> élément<?= $total > 1 ? 's' : '' ?>
                (<?= (int) $perPage ?> par page).
            <?php else: ?>
                Aucune donnée enregistrée pour le moment.
            <?php endif; ?>
        </p>

        <?php if ($current === 'contact'): ?>
            <p class="text-muted small mb-3">
                Astuce : double-cliquez sur une ligne pour lire le message complet dans une modale.
            </p>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if (empty($entries)): ?>
                    <p class="text-muted mb-0">Aucune entrée dans ce journal.</p>
                <?php elseif ($current === 'login'): ?>
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
                <?php else: ?>
                    <?php $contactRows = []; ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="contactLogTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Nom</th>
                                    <th>E-mail</th>
                                    <th>
                                        <span class="d-inline-flex align-items-center gap-1"
                                              data-bs-toggle="tooltip" data-bs-placement="top"
                                              data-bs-title="Double-cliquez sur une ligne pour ouvrir le message complet dans une modale."
                                              aria-label="Double-cliquez sur une ligne pour ouvrir le message complet dans une modale."
                                              style="cursor:help;">
                                            Message
                                        </span>
                                    </th>
                                    <th>Copie</th>
                                    <th>Envoi</th>
                                    <th>Erreur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
                                    <?php if (isset($entry['data'])): ?>
                                        <?php
                                        $d = $entry['data'];
                                        $contactRows[] = [
                                            'name' => (string) ($d['name'] ?? ''),
                                            'email' => (string) ($d['email'] ?? ''),
                                            'time' => $entry['time'],
                                            'copy' => !empty($d['copy']),
                                            'mail' => (string) ($d['mail'] ?? ''),
                                            'message' => (string) ($d['message'] ?? ''),
                                        ];
                                        ?>
                                        <tr class="contact-row" data-index="<?= count($contactRows) - 1 ?>"
                                            style="cursor:pointer;"
                                            title="Double-cliquez pour lire le message complet">
                                            <td class="text-nowrap small"><?= htmlspecialchars($entry['time'] !== '' ? $entry['time'] : '—') ?></td>
                                            <td class="small"><?= htmlspecialchars((string) ($d['name'] ?? '—')) ?></td>
                                            <td class="small"><?= htmlspecialchars((string) ($d['email'] ?? '—')) ?></td>
                                            <td class="small text-muted" style="max-width:300px;">
                                                <span class="d-inline-block text-truncate" style="max-width:100%;"
                                                      title="<?= htmlspecialchars((string) ($d['message'] ?? ''), ENT_QUOTES) ?>">
                                                    <?= htmlspecialchars((string) ($d['message'] ?? '—')) ?>
                                                </span>
                                            </td>
                                            <td class="small"><?= !empty($d['copy']) ? 'Oui' : '—' ?></td>
                                            <td class="small">
                                                <?php if (($d['mail'] ?? '') === 'sent'): ?>
                                                    <span class="badge bg-success">Envoyé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">En échec</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars((($d['error'] ?? '') !== '' ? $d['error'] : '—')) ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td class="text-nowrap small"><?= htmlspecialchars($entry['time'] !== '' ? $entry['time'] : '—') ?></td>
                                            <td colspan="6" class="small text-muted">
                                                <code><?= htmlspecialchars((string) ($entry['raw'] ?? '')) ?></code>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($contactRows): ?>
                        <script>
                            var contactLogRows = <?= json_encode($contactRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                        </script>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($pages > 1): ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($pages, $start + 4);
            $start = max(1, $end - 4);
            ?>
            <nav class="mt-3" aria-label="Pagination des journaux">
                <ul class="pagination pagination-sm justify-content-center flex-wrap">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $base ?>?f=<?= urlencode($current) ?>&page=<?= max(1, $page - 1) ?>">Précédent</a>
                    </li>
                    <?php for ($p = $start; $p <= $end; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $base ?>?f=<?= urlencode($current) ?>&page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $base ?>?f=<?= urlencode($current) ?>&page=<?= min($pages, $page + 1) ?>">Suivant</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

        <form action="<?= route('admin.logs.clear') ?>" method="POST" class="mt-3"
              onsubmit="return confirm('Vider définitivement <?= $current === 'login' ? 'le journal de connexion' : 'le journal des messages de contact' ?> ? Cette action est irréversible.');">
            <?= csrf_field() ?>
            <input type="hidden" name="f" value="<?= htmlspecialchars($current) ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">Vider ce journal</button>
        </form>

        <p class="text-muted small mt-3 mb-0">
            Emplacement : <code>storage/logs/<?= $current === 'login' ? 'login.log' : 'contact.log' ?></code>
            (dossier protégé, inaccessible depuis le web).
        </p>
    </div>
</div>

<div class="modal fade" id="contactMessageModal" tabindex="-1" aria-labelledby="contactMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="contactMessageModalLabel" style="color:#003878;">Message de contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge bg-light text-dark fw-normal"><strong>Date :</strong> <span id="cmTime">—</span></span>
                    <span class="badge bg-light text-dark fw-normal"><strong>Copie :</strong> <span id="cmCopy">—</span></span>
                    <span class="badge" id="cmMail">—</span>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="border rounded bg-light p-3 h-100">
                            <div class="text-uppercase text-muted small fw-bold mb-1">Nom</div>
                            <div id="cmName" class="small">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded bg-light p-3 h-100">
                            <div class="text-uppercase text-muted small fw-bold mb-1">E-mail</div>
                            <div id="cmEmail" class="small">—</div>
                        </div>
                    </div>
                </div>
                <div class="border rounded bg-light p-3">
                    <div class="text-uppercase text-muted small fw-bold mb-2">Message</div>
                    <div id="cmMessage" class="small" style="white-space:pre-wrap; word-break:break-word;">—</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    function openContactMessage(data) {
        if (!data || typeof bootstrap === 'undefined') {
            return;
        }
        document.getElementById('cmTime').textContent = data.time || '—';
        document.getElementById('cmCopy').textContent = data.copy ? 'Oui' : 'Non';
        document.getElementById('cmName').textContent = data.name || '—';
        document.getElementById('cmEmail').textContent = data.email || '—';

        var mailEl = document.getElementById('cmMail');
        var mailOk = (data.mail || '') === 'sent';
        mailEl.textContent = mailOk ? 'Envoyé' : 'En échec';
        mailEl.className = mailOk ? 'badge bg-success' : 'badge bg-danger';

        document.getElementById('cmMessage').textContent = data.message || '—';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('contactMessageModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof bootstrap !== 'undefined') {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
        var rows = document.querySelectorAll('#contactLogTable tbody tr.contact-row');
        if (rows.length === 0 || typeof contactLogRows === 'undefined' || !Array.isArray(contactLogRows)) {
            return;
        }
        rows.forEach(function (row) {
            row.addEventListener('dblclick', function () {
                var idx = parseInt(row.getAttribute('data-index'), 10);
                openContactMessage(contactLogRows[idx]);
            });
        });
    });
})();
</script>