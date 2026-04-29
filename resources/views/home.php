<header class="hero">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-6" style="font-weight:700">Apprenez l'informatique moderne — pratique, sécurisée, accessible</h1>
                <p class="lead text-muted">Ressources pédagogiques pour BTS SIO, formations CPI (Bac+3) et parcours Expert (Bac+5). DokuWiki protégé par CloudFlare & proxy applicatif, proposant des TP réalistes et sécurisés.</p>
                <div class="my-3 d-flex gap-2 flex-wrap">
                    <div class="tech-chip" title="Golang"><div class="tech-icon">Go</div><small>Golang</small></div>
                    <div class="tech-chip" title="PowerShell"><div class="tech-icon">PS</div><small>PowerShell</small></div>
                    <div class="tech-chip" title="Web"><div class="tech-icon">Web</div><small>HTML/JS/PHP</small></div>
                    <div class="tech-chip" title="GNU/Linux"><div class="tech-icon">Tux</div><small>GNU/Linux</small></div>
                    <div class="tech-chip" title="Windows"><div class="tech-icon">Win</div><small>Windows</small></div>
                </div>
                <div class="mt-4 d-flex gap-3">
                    <a class="btn btn-cta btn-lg" id="visitBtn">Accéder au DokuWiki</a>
                    <a class="btn btn-outline-secondary btn-lg" id="btnPlan">Plan du site</a>
                    <a class="btn btn-outline-secondary btn-lg" id="openSchemaBtn">Infrastructure</a>
                </div>
                <p class="mt-3 small text-muted">Site protégé par CloudFlare. Les applications pédagogiques sont servies via un proxy PHP pour isoler les environnements de TP.</p>
            </div>
            <div class="col-lg-6 position-relative">
                <div class="card feature-card p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 style="font-weight:700">Parcours & compétences</h5>
                            <p class="mb-2 text-muted">Des TP progressifs et contextualisés : réseaux, scripting, cybersécurité, Cloud, DevOps, gestion de projet.</p>
                        </div>
                        <div class="text-end"><span class="badge bg-primary">BTS → Bac+5</span></div>
                    </div>
                    <ul class="list-unstyled mt-3">
                        <li class="d-flex align-items-start mb-2"><div class="me-3">&#128736;&#65039;</div><div><strong>Ateliers pratiques</strong><br><small class="text-muted">TP encadrés reproduisant des environnements réels.</small></div></li>
                        <li class="d-flex align-items-start mb-2"><div class="me-3">&#128272;</div><div><strong>Sécurité et isolation</strong><br><small class="text-muted">CloudFlare + proxy applicatif pour protéger les services pédagogiques.</small></div></li>
                        <li class="d-flex align-items-start mb-2"><div class="me-3">&#128200;</div><div><strong>Méthodes projet</strong><br><small class="text-muted">Agile & Cycle en V pour préparer la gestion de projets et les certifications.</small></div></li>
                    </ul>
                </div>
                <div class="mt-4 p-3">
                    <div id="miniIDE" class="border rounded" style="background:#0b1220;color:#dbeafe;padding:12px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;min-height:160px">
                        <div style="opacity:.85;font-size:.85rem"></div>
                        <pre id="console" style="margin:0;white-space:pre-wrap"></pre>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3 small text-muted mysterious" id="search">
            Survolez. Cliquez. Certains éléments réagissent comme si le site avait laissé des portes dérobées...
            <span class="tooltip">Le développement web est un art… pour celui qui sait regarder derrière le code.</span>
        </div>
    </div>
</header>
<main class="container my-5">
    <div class="row mt-5 mb-5">
        <div class="col-md-4"><div class="card feature-card p-3" style="background:#f8f9fa; color:#212529; padding-bottom:20px;"><h6><strong>DevOps & CI</strong></h6><p class="small text-muted" style="color:#495057;">Pipelines, conteneurs, intégration continue et pratiques d'automatisation.</p></div></div>
        <div class="col-md-4"><div class="card feature-card p-3" style="background:#f8f9fa; color:#212529;"><h6><strong>Administration Systèmes</strong></h6><p class="small text-muted">Linux, Windows Server, scripting, supervision, dépannage et gestion des utilisateurs.</p></div></div>
        <div class="col-md-4"><div class="card feature-card p-3" style="background:#f8f9fa; color:#212529;"><h6><strong>Cybersécurité</strong></h6><p class="small text-muted" style="color:#495057;">Hardening, tests d'intrusion, chiffrement et gestion des incidents.</p></div></div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <article>
                <h3>Comment accéder au site cours-reseaux ?</h3>
                <p class="text-muted">cours-reseaux.fr est un site privé dont l'accès est réservé en priorité aux étudiants du CFAI LDA. Il propose une approche pédagogique progressive : démonstrations, TP guidés, exercices liés à des scénarios métiers réalistes. Les contenus sont maintenus, sécurisés et adaptés aux cursus BTS SIO, Bac+3 CPI (CPLR) et Bac+5 Expert Cyber.</p>
                <section class="mt-4">
                    <h5>Thématiques clés</h5>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-light border" style="cursor:default;">Systèmes</span>
                        <span class="badge bg-light border" style="cursor:default;">Scripting</span>
                        <span id="cipherStrike" class="badge bg-light border" style="cursor:default;">Hacking</span>
                        <span class="badge bg-light border" style="cursor:default;">ITIL</span>
                        <span class="badge bg-light border" style="cursor:default;">DevOps</span>
                        <span class="badge bg-light border" style="cursor:default;">Gestion de projet</span>
                    </div>
                </section>
                <div id="wordCloud" class="mt-4" style="height:180px;">
                    <div id="wordCloudInner"></div>
                </div>
            </article>
        </div>
        <aside class="col-md-4">
            <div class="card mt-3 p-3">
                <h6>Statut</h6>
                <p class="small text-muted">Proxy applicatif en place — sessions isolées pour travaux pratiques. CloudFlare filtre les attaques et sert le contenu statique.</p>
                <div class="progress" style="height:10px; cursor:pointer;"><div class="progress-bar" role="progressbar" style="width:72%"></div></div>
            </div>
        </aside>
    </div>
</main>
