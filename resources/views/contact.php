<header class="hero">
    <div class="container">
        <div class="contact-wrapper">
            <?php if ($success): ?>
                <div class="alert alert-success">Votre message a bien été envoyé. Merci !</div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form action="<?= route('contact') ?>" method="POST">
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
                <div class="text-center">
                    <button type="submit" class="btn-cta">Envoyer le message</button>
                </div>
            </form>
        </div>
    </div>
</header>
