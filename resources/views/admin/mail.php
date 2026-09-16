<div class="row justify-content-center">
    <div class="col-md-9">
        <h2 class="h4 fw-bold mb-1" style="color:#003878;">Envoi des e-mails</h2>
        <p class="text-muted mb-4">Choisissez le moteur utilisé par le site pour envoyer les messages du formulaire de contact.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($test_success)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($test_success) ?></div>
        <?php endif; ?>

        <?php if (!empty($test_error)): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($test_error) ?></div>
        <?php endif; ?>

        <?php $engine = $form['engine'] ?? 'mail'; ?>
        <?php $brevo = $form['brevo'] ?? []; ?>

        <form method="POST" action="<?= route('admin.mail') ?>" class="card border-0 shadow-sm">
            <?= csrf_field() ?>
            <div class="card-body p-4">
                <div class="mb-4">
                    <label class="form-label" for="engine">Moteur d'envoi</label>
                    <select class="form-select" id="engine" name="engine" onchange="toggleBrevo()">
                        <option value="mail" <?= $engine === 'mail' ? 'selected' : '' ?>>
                            Envoi standard (fonction PHP &laquo; mail() &raquo;)
                        </option>
                        <option value="brevo" <?= $engine === 'brevo' ? 'selected' : '' ?>>
                            Brevo.com (SMTP)
                        </option>
                    </select>
                    <div class="form-text">
                        Standard : envoy&eacute; directement par le serveur d'h&eacute;bergement (recommand&eacute; sur un
                        h&eacute;bergement PHP). Brevo : envoy&eacute; via les serveurs de Brevo.com, utile quand la
                        fonction mail() est bloqu&eacute;e par l'h&eacute;bergeur.
                    </div>
                    <div id="engine-warning" class="alert alert-warning mt-3 mb-0 <?= $engine === 'mail' ? '' : 'd-none' ?>">
                        <strong>Avertissement&nbsp;:</strong> avec le moteur standard, les e-mails envoy&eacute;s depuis
                        la page contact peuvent &ecirc;tre filtr&eacute;s silencieusement par l'h&eacute;bergeur
                        (domaine secondaire sans DKIM). Le visiteur verra &laquo;&nbsp;message envoy&eacute;&nbsp;&raquo;
                        alors que l'e-mail n'arrivera peut-&ecirc;tre jamais.
                        <strong>Moteur Brevo (SMTP) recommand&eacute;</strong> pour garantir la livraison.
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" for="from_name">Nom de l'exp&eacute;diteur</label>
                        <input type="text" class="form-control" id="from_name" name="from_name"
                               value="<?= htmlspecialchars($form['from_name'] ?? 'Cours-Reseaux') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="from">E-mail exp&eacute;diteur</label>
                        <input type="email" class="form-control" id="from" name="from"
                               value="<?= htmlspecialchars($form['from'] ?? 'contact@cours-reseaux.fr') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="to">Destinataire des messages</label>
                        <input type="email" class="form-control" id="to" name="to"
                               value="<?= htmlspecialchars($form['to'] ?? 'contact@cours-reseaux.fr') ?>">
                    </div>
                </div>

                <div id="brevo-block" class="border rounded bg-light p-3 mb-4"
                     style="<?= $engine === 'brevo' ? '' : 'display:none;' ?>">
                    <h6 class="fw-bold text-uppercase mb-3" style="color:#003878;">Brevo.com — SMTP</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="smtp_host">Serveur SMTP</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                   value="<?= htmlspecialchars($brevo['smtp_host'] ?? 'smtp-relay.brevo.com') ?>">
                            <div class="form-text">Par d&eacute;faut : smtp-relay.brevo.com</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="smtp_port">Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                   value="<?= htmlspecialchars((string) ($brevo['smtp_port'] ?? 587)) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="smtp_security">S&eacute;curit&eacute;</label>
                            <select class="form-select" id="smtp_security" name="smtp_security">
                                <option value="tls" <?= ($brevo['smtp_security'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>
                                    STARTTLS (port 587)
                                </option>
                                <option value="ssl" <?= ($brevo['smtp_security'] ?? '') === 'ssl' ? 'selected' : '' ?>>
                                    SSL/TLS implicite (port 465)
                                </option>
                                <option value="none" <?= ($brevo['smtp_security'] ?? '') === 'none' ? 'selected' : '' ?>>
                                    Sans chiffrement
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="smtp_user">Identifiant SMTP</label>
                            <input type="text" class="form-control" id="smtp_user" name="smtp_user"
                                   value="<?= htmlspecialchars($brevo['smtp_user'] ?? '') ?>"
                                   placeholder="Identifiant SMTP fourni par Brevo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="smtp_password">Cl&eacute; / mot de passe SMTP</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                                   value="<?= htmlspecialchars($brevo['smtp_password'] ?? '') ?>"
                                   placeholder="Cl&eacute; SMTP ou cl&eacute; API Brevo">
                            <div class="form-text">
                                La cl&eacute; est stock&eacute;e en clair dans le fichier config/mail.php (prot&eacute;g&eacute;
                                par le serveur). Vous pouvez r&eacute;voquer et r&eacute;g&eacute;n&eacute;rer la cl&eacute; dans le
                                compte Brevo.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border rounded bg-light p-3 mb-4">
                    <h6 class="fw-bold text-uppercase mb-3" style="color:#003878;">Logs de débogage</h6>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="log_enabled" name="log_enabled" value="1"
                               <?= !empty($form['log_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="log_enabled">
                            Activer les logs de débogage d'envoi de mails
                        </label>
                    </div>
                    <div class="form-text mt-2">
                        Quand cette option est activ&eacute;e, chaque tentative d'envoi est consign&eacute;e dans
                        <code>storage/logs/mailer.log</code> ainsi que la validation du formulaire de contact dans
                        <code>storage/logs/contact.log</code>.
                        Pensez &agrave; d&eacute;sactiver l'option une fois le d&eacute;bogage termin&eacute;.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <button type="submit" class="btn btn-outline-info" formaction="<?= route('admin.mail.test') ?>"
                            formmethod="post">
                        Tester l'envoi
                    </button>
                    <a href="<?= route('admin.dashboard') ?>" class="btn btn-outline-secondary ms-auto">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleBrevo() {
    var block = document.getElementById('brevo-block');
    if (block) {
        block.style.display = document.getElementById('engine').value === 'brevo' ? '' : 'none';
    }
    var warning = document.getElementById('engine-warning');
    if (warning) {
        warning.classList.toggle('d-none', document.getElementById('engine').value !== 'mail');
    }
}
</script>