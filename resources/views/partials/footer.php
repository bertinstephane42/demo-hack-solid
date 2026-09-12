<footer class="bg-light py-4 border-top">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <span id="linkedin-name" class="linkedin-profile"><strong>Stéphane BERTIN</strong></span><br>
            <small class="text-muted">&copy; <?= $year ?? date('Y') ?> — Ressources pédagogiques informatiques</small>
        </div>
        <div class="text-end">
            <small class="text-muted">Hébergement sécurisé • CloudFlare • Proxy PHP</small>
        </div>
    </div>
    <script>
    (function () {
        var parts = ["s", "t", "e", "p", "h", "a", "n", "e", "-", "b", "e", "r", "t", "i", "n", "4", "2"];
        var el = document.getElementById("linkedin-name");
        if (el) {
            var username = parts.join("");
            el.innerHTML = '<a href="https://www.linkedin.com/in/' + username + '/" target="_blank" rel="noopener noreferrer"><strong>Stéphane BERTIN</strong></a>';
        }
    })();
    </script>
    <canvas id="fireworks-canvas" style="position:fixed;bottom:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;"></canvas>
    <div id="fireworks-hint" style="position:fixed; bottom:40px; left:50%; transform:translateX(-50%); color:white; font-size:18px; font-weight:600; text-shadow:0 0 8px black; opacity:0; transition:opacity .4s; pointer-events:none; z-index:10000;">
        Cliquez pour activer le son des explosions
    </div>
</footer>
