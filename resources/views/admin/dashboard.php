<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> py-2">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 h-100">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-1" style="color:#003878;">Envoi d'un mail</h2>
                <p class="text-muted small mb-4">Rédigez un message et envoyez-le via Brevo.</p>

                <form action="<?= route('admin.send') ?>" method="POST">
                    <input type="hidden" name="_csrftoken" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="toEmail" class="form-label">Destinataire (email) <span class="text-danger">*</span></label>
                            <input type="email" name="to_email" id="toEmail" class="form-control"
                                   required maxlength="254" placeholder="etudiant@exemple.fr">
                        </div>
                        <div class="col-md-5">
                            <label for="toName" class="form-label">Nom du destinataire</label>
                            <input type="text" name="to_name" id="toName" class="form-control"
                                   maxlength="120" placeholder="Prénom Nom">
                        </div>
                        <div class="col-12">
                            <label for="subject" class="form-label">Sujet <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject" class="form-control"
                                   required maxlength="120" placeholder="Objet du message">
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label">Message (HTML ou texte) <span class="text-danger">*</span></label>
                            <textarea name="message" id="message" class="form-control" rows="8"
                                      placeholder="Bonjour, ..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-text mb-3">
                                Vous pouvez utiliser du HTML simple (&lt;p&gt;, &lt;strong&gt;, &lt;a&gt;, &lt;br&gt;).
                            </div>
                            <button type="submit" class="btn btn-primary fw-bold px-4">Envoyer via Brevo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 mb-4">
            <div class="card-body p-4">
                <h3 class="h6 fw-bold mb-1" style="color:#003878;">Configuration Brevo</h3>
                <p class="text-muted small mb-3">
                    Clé API <em>transactionnelle</em> (Brevo → SMTP &amp; API → API Keys).
                </p>
                <form action="<?= route('admin.settings') ?>" method="POST">
                    <input type="hidden" name="_csrftoken" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="mb-3">
                        <label for="apiKey" class="form-label">Clé API Brevo</label>
                        <input type="password" name="api_key" id="apiKey" class="form-control"
                               value="<?= htmlspecialchars($settings['api_key'] ?? '') ?>"
                               placeholder="xkeysib-..." autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="senderName" class="form-label">Nom de l'expéditeur</label>
                        <input type="text" name="sender_name" id="senderName" class="form-control"
                               value="<?= htmlspecialchars($settings['sender_name'] ?? '') ?>" maxlength="120">
                    </div>
                    <div class="mb-3">
                        <label for="senderEmail" class="form-label">Email de l'expéditeur</label>
                        <input type="email" name="sender_email" id="senderEmail" class="form-control"
                               value="<?= htmlspecialchars($settings['sender_email'] ?? '') ?>" maxlength="254">
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100 fw-bold">
                        Enregistrer les paramètres
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0">
            <div class="card-body p-4">
                <h3 class="h6 fw-bold mb-2" style="color:#003878;">Fonctionnement</h3>
                <ul class="small text-muted mb-0 ps-3">
                    <li>Les mails sont envoyés via l'API transactionnelle Brevo.</li>
                    <li class="mt-1">Le quota de l'hébergeur (mail()) ne s'applique pas à Brevo.</li>
                    <li class="mt-1">Toutes les tentatives sont journalisées dans <code>storage/logs/logs</code>.</li>
                    <li class="mt-1">Connecté en tant que : <strong><?= htmlspecialchars($admin_email) ?></strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>