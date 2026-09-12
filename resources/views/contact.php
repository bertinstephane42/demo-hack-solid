<header class="hero">
    <div class="container">
        <div class="contact-wrapper">
            <?php if ($success): ?>
                <div class="alert alert-success">Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.</div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form action="<?= route('contact') ?>" method="POST" id="contactForm">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($form_token) ?>">
                <input type="hidden" name="_time" value="<?= (int) $form_time ?>">
                <div style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden" aria-hidden="true">
                    <label for="websiteInput">Laissez ce champ vide</label>
                    <input type="text" name="website" id="websiteInput" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-floating mb-3">
                    <input type="text" name="name" class="form-control" id="nameInput" placeholder="Votre nom" required minlength="6" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                    <label for="nameInput">Votre nom et prénom</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="emailInput" placeholder="Votre email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <label for="emailInput">Votre email</label>
                </div>
                <div class="mb-4">
                    <label for="captchaInput" class="form-label">Combien font <?= (int) $captcha_a ?> + <?= (int) $captcha_b ?> ?</label>
                    <input type="text" name="captcha" class="form-control" id="captchaInput" inputmode="numeric" autocomplete="off" required>
                </div>
                <div class="form-floating mb-4">
                    <textarea name="message" class="form-control" id="messageInput" placeholder="Votre message" style="height:160px" required minlength="7"></textarea>
                    <label for="messageInput">Votre message</label>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="copyToggle" name="copy" value="1"
                           <?= !empty($old['copy']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="copyToggle">Recevoir une copie du mail</label>
                </div>
                <div class="form-floating mb-4" id="copyFieldGroup" style="<?= empty($old['copy']) ? 'display:none;' : '' ?>">
                    <input type="email" name="copy_email" class="form-control" id="copyEmailInput"
                           placeholder="Votre mail" value="<?= htmlspecialchars($old['copy_email'] ?? '') ?>"
                           <?= empty($old['copy']) ? 'disabled' : '' ?>>
                    <label for="copyEmailInput">Votre mail (pour la copie)</label>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn-cta">Envoyer le message</button>
                </div>
            </form>
        </div>
    </div>
</header>

<div class="modal fade" id="copyConfirmModal" tabindex="-1" role="dialog"
     aria-labelledby="copyConfirmModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content"
             style="border:0;border-radius:14px;overflow:hidden;box-shadow:0 18px 50px rgba(0,56,120,.18);">
            <div class="modal-header"
                 style="background:#003878;border-bottom:3px solid #009DEA;padding:1rem 1.25rem;">
                <h5 class="modal-title" id="copyConfirmModalTitle"
                    style="color:#fff;font-weight:700;font-size:1.05rem;">
                    <span style="color:#FB7F0A;">Confirmation</span> &mdash; envoi d'une copie
                </h5>
            </div>
            <div class="modal-body" style="padding:1.5rem 1.25rem;">
                <div class="d-flex gap-3">
                    <div style="flex:0 0 auto;width:42px;height:42px;border-radius:50%;background:rgba(0,157,234,.12);display:flex;align-items:center;justify-content:center;color:#009DEA;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor"
                             viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                        </svg>
                    </div>
                    <div style="color:#2b3a4a;line-height:1.6;">
                        <p style="margin:0 0 .5rem;">La copie du message sera envoy&eacute;e &agrave;&nbsp;:</p>
                        <p style="margin:0 0 1rem;font-weight:700;color:#003878;word-break:break-all;"
                           id="copyConfirmEmail"></p>
                        <p style="margin:0;font-size:.95rem;">
                            Cette adresse est diff&eacute;rente de votre adresse
                            <strong id="copyConfirmSource" style="color:#003878;"></strong>.
                            Confirmez-vous l'envoi de la copie &agrave; cette adresse&nbsp;?
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #eef2f6;padding:1rem 1.25rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Revenir au formulaire
                </button>
                <button type="button" class="btn btn-cta" id="copyConfirmYes">
                    Confirmer l'envoi de la copie
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var toggle = document.getElementById('copyToggle');
    var group = document.getElementById('copyFieldGroup');
    var input = document.getElementById('copyEmailInput');
    var source = document.getElementById('emailInput');
    var synced = false;

    function apply() {
        var active = toggle.checked;
        group.style.display = active ? '' : 'none';
        input.disabled = !active;
        if (active) {
            if (input.value === '') {
                input.value = source.value;
                synced = true;
            }
            input.focus();
        } else {
            synced = false;
        }
    }

    function isValidEmail(value) {
        return /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(value.trim());
    }

    function updateToggle() {
        var valid = isValidEmail(source.value);
        toggle.disabled = !valid;
        if (!valid) {
            toggle.checked = false;
        }
        apply();
    }

    toggle.addEventListener('change', apply);

    source.addEventListener('input', function () {
        if (toggle.checked && synced && isValidEmail(source.value)) {
            input.value = source.value;
        }
        updateToggle();
    });

    input.addEventListener('input', function () {
        synced = (input.value === source.value);
    });

    var modalEl = document.getElementById('copyConfirmModal');
    var copyConfirmEmail = document.getElementById('copyConfirmEmail');
    var copyConfirmSource = document.getElementById('copyConfirmSource');
    var confirmed = false;

    function openCopyConfirm(copyValue, main) {
        copyConfirmEmail.textContent = copyValue;
        copyConfirmSource.textContent = main;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.getElementById('contactForm').addEventListener('submit', function (e) {
        if (confirmed) {
            confirmed = false;
            return;
        }
        if (!toggle.checked) {
            return;
        }
        var main = source.value.trim();
        var copyValue = input.value.trim();
        if (isValidEmail(copyValue) && copyValue.toLowerCase() !== main.toLowerCase()) {
            e.preventDefault();
            openCopyConfirm(copyValue, main);
        }
    });

    document.getElementById('copyConfirmYes').addEventListener('click', function () {
        confirmed = true;
        bootstrap.Modal.getInstance(modalEl).hide();
        var form = document.getElementById('contactForm');
        if (form.requestSubmit) {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    updateToggle();
})();
</script>