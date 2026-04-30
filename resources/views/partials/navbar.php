<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container d-flex align-items-center justify-content-between">
        <a class="navbar-brand d-flex align-items-center gap-3">
            <span class="logo-mark position-relative">
                CR
                <div id="tux-popup"></div>
            </span>
            <div class="d-none d-md-block">
                <div style="font-weight:700">Cours-Réseaux</div>
                <small class="text-muted">BTS SIO • CPI • Expert Cyber</small>
            </div>
        </a>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" id="btnDoku">Accéder au DokuWiki</button>
            <a href="<?= route('contact') ?>" class="btn btn-outline-secondary">Contact</a>
            <button id="helpBtn" class="btn btn-outline-primary px-3 py-2 shadow-sm" style="border-width:2px; font-weight:600; letter-spacing:0.5px;">
                <span style="font-family:monospace;">Aide</span>
            </button>
            <button class="btn btn-outline-light btn-sm subtle-btn hacker-fade" id="hackerBtn">0xH4X0</button>
        </div>
    </div>
</nav>
