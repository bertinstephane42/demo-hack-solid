<header class="hero">
    <div class="container">
        <div class="contact-wrapper">
            <?php if ($success): ?>
                <div class="alert alert-success">Votre message a bien été envoyé. Merci !</div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form action="/proxy" method="POST">
                <input type="hidden" name="id" value="contact">
                <input type="hidden" name="form_submitted" value="1">
                <div class="form-floating mb-3">
                    <input type="text" name="name" class="form-control" id="nameInput" placeholder="Votre nom" required value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                    <label for="nameInput">Votre nom et prénom</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" name="email" class="form-control" id="emailInput" placeholder="Votre email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <label for="emailInput">Votre email</label>
                </div>
                <div class="form-floating mb-4">
                    <textarea name="message" class="form-control" id="messageInput" placeholder="Votre message" style="height:160px" required></textarea>
                    <label for="messageInput">Votre message</label>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn-cta">Envoyer le message</button>
                </div>
            </form>
        </div>
    </div>
</header>
