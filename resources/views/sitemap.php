<div class="container sitemap-page py-4">
    <div class="sitemap-head d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="h3 mb-1">Plan du site</h1>
            <p id="menuTitle" class="sitemap-level mb-0" aria-live="polite">Catégories</p>
        </div>
        <button class="back-btn" id="backBtn" type="button" hidden>← Retour</button>
    </div>

    <div class="menu-frame-outer mt-3">
        <div class="menu-frame glass-panel">
            <div id="menuContainer" class="card-grid" aria-labelledby="menuTitle"></div>
        </div>
    </div>

    <section id="legendBox" aria-labelledby="legendTitle">
        <h2 id="legendTitle" class="h6">Légende des boutons</h2>
        <div class="legend-items">
            <span class="legend-chip"><span class="legend-swatch legend-category" aria-hidden="true"></span>Catégorie / sous-catégorie</span>
            <span class="legend-chip"><span class="legend-swatch legend-proxy" aria-hidden="true"></span>Proxy PHP / DokuWiki</span>
            <span class="legend-chip"><span class="legend-swatch legend-doku" aria-hidden="true"></span>DokuWiki</span>
            <span class="legend-chip"><span class="legend-swatch legend-site" aria-hidden="true"></span>Ressource interne</span>
            <span class="legend-chip"><span class="legend-swatch legend-external" aria-hidden="true"></span>Ressource externe</span>
        </div>
    </section>
</div>
<script>
window.SITEMAP_DATA = <?= $menu->toJson() ?>;
</script>
