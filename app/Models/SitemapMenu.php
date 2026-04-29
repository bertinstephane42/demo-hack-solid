<?php

namespace App\Models;

class SitemapMenu
{
    protected array $menu = [];

    public function __construct()
    {
        $this->menu = $this->load();
    }

    public function categories(): array
    {
        return array_keys($this->menu);
    }

    public function getCategory(string $key): ?array
    {
        return $this->menu[$key] ?? null;
    }

    public function getSubcategories(string $categoryKey): array
    {
        return $this->menu[$categoryKey]['subcategories'] ?? [];
    }

    public function getItems(string $categoryKey): array
    {
        return $this->menu[$categoryKey]['items'] ?? [];
    }

    public function getSubcategoryItems(string $categoryKey, string $subKey): array
    {
        return $this->menu[$categoryKey]['subcategories'][$subKey]['items'] ?? [];
    }

    public function isClickableSubcategory(string $categoryKey, string $subKey): bool
    {
        $items = $this->getSubcategoryItems($categoryKey, $subKey);
        return array_filter($items, fn($item) => !empty($item['url'] ?? '')) !== [];
    }

    public function isClickableCategory(string $categoryKey): bool
    {
        $subcats = $this->getSubcategories($categoryKey);
        $hasSubItems = array_reduce($subcats, fn($carry, $sub) => $carry || $this->isClickableSubcategory($categoryKey, $sub), false);
        $hasDirectItems = $this->getItems($categoryKey) !== [];
        return $hasSubItems || $hasDirectItems;
    }

    public function hasSubcategories(string $categoryKey): bool
    {
        return !empty($this->getSubcategories($categoryKey));
    }

    public function hasDirectItems(string $categoryKey): bool
    {
        return !empty($this->getItems($categoryKey));
    }

    public function toJson(): string
    {
        return json_encode($this->menu, JSON_UNESCAPED_UNICODE);
    }

    protected function load(): array
    {
        return [
            'BTS 1 SIO' => [
                'subcategories' => [
                    'Roadmap' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/intro/presentation.html', 'label' => 'Première séance'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/intro/roadmap1.html', 'label' => 'Plan des cours'],
                        ],
                    ],
                    'Commun' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:bloc1:1:menu', 'label' => 'Bloc 1'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:bloc3:1:identite:menu', 'label' => 'Bloc 3'],
                        ],
                    ],
                    'SISR' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:bloc2:acturix:menu', 'label' => 'Plateforme professionnelle Acturix'],
                        ],
                    ],
                    'Projets techniques' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:ppe1:sisr:tae', 'label' => 'TAE'],
                        ],
                    ],
                ],
                'items' => [
                    ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:progression:bts1:infos', 'label' => 'Informations techniques'],
                    ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:progression:bts1:evenements', 'label' => 'Evenements importants'],
                ],
            ],
            'BTS 2 SIO' => [
                'subcategories' => [
                    'Roadmap' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts2/intro/presentation.html', 'label' => 'Première séance'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts2/intro/roadmap2.html', 'label' => 'Plan des cours'],
                        ],
                    ],
                    'SISR' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:bloc1:2:si7:menu', 'label' => 'Bloc 1'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:bloc3:sisr2:cyberedu2:menu', 'label' => 'Bloc 3'],
                        ],
                    ],
                    'CCF' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:ppe2:sisr:tae', 'label' => 'TAE'],
                        ],
                    ],
                    'Commentaires' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/discussion:start', 'label' => 'Vos suggestions'],
                        ],
                    ],
                ],
                'items' => [
                    ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:progression:bts2:infos', 'label' => 'Informations techniques'],
                    ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:progression:bts2:evenements', 'label' => 'Evenements importants'],
                ],
            ],
            'Bachelor CPI' => [
                'items' => [
                    ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:bachelor:menu', 'label' => 'Cours de Bachelor'],
                ],
            ],
            'Master e2i' => [
                'items' => [
                    ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cours:master:menu', 'label' => 'Cours de Master'],
                ],
            ],
            'Boîte à outils' => [
                'subcategories' => [
                    'A propos du BTS' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/doc:bts:menu', 'label' => 'Informations sur le BTS'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/doc:bts:epreuves', 'label' => 'Les épreuves du BTS'],
                            ['url' => 'https://cours-reseaux.fr/tp/bot/ccf.html', 'label' => 'Chatbot des épreuves'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/doc:annales:menu', 'label' => 'Les annales du BTS'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/web:veille', 'label' => 'Veille technologique'],
                        ],
                    ],
                    'Apprendre' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/vimmaster/index.html', 'label' => 'Se former à Vim'],
                        ],
                    ],
                    'Audit du code' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/check-php/index.php', 'label' => 'Audit de sécurité PHP'],
                        ],
                    ],
                    'Créer des rapports' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/mdwriter/public/login.php', 'label' => 'mdWriter'],
                        ],
                    ],
                    'Dessiner' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/excalidrawjs', 'label' => 'ExcalidrawJS'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/bts1:cours:excalidraw', 'label' => 'Info excalidrawJS'],
                        ],
                    ],
                    'Documentation' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/howto:guides', 'label' => 'Aide-mémoire et guides'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/howto:expertise', 'label' => 'Savoir-faire'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/web:sites', 'label' => 'Sites internet utiles'],
                        ],
                    ],
                    'Partage de mots de passe' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/PrivateBin/', 'label' => 'PrivateBin'],
                        ],
                    ],
                    'Reporter un problème' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/osTicket/login.php', 'label' => 'osTicket - Création de ticket'],
                            ['url' => 'https://cours-reseaux.fr/osTicket/scp/login.php', 'label' => 'osTicket - Accès technicien'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/docs/doc_osticket.html', 'label' => 'Comment ouvrir un ticket avec osTicket'],
                        ],
                    ],
                ],
            ],
            'Technique' => [
                'subcategories' => [
                    'GitHub' => [
                        'items' => [
                            ['url' => 'https://github.com/bertinstephane42/', 'label' => 'Github bertinstephane42'],
                        ],
                    ],
                    'Goodies' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/goodies/web-02.html', 'label' => 'Modem Web v0.2'],
                        ],
                    ],
                    'IA - LLM' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/ia/danger_ia.html', 'label' => 'Utilisez l\'IA de manière responsable'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/ia/intro_ia.html', 'label' => 'Fonctionnement d\'un LLM'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/ia/lois_ia.html', 'label' => 'Les 19 lois de l\'utilisation de l\'IA'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/ia/routing_ia.html', 'label' => 'Fonctionnement du routing IA'],
                        ],
                    ],
                    'Infrastructure' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/docs/infra.html', 'label' => 'Infrastructure web'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/docs/infra-prod.html', 'label' => 'Schéma de l\'infrastructure pédagogique'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/proxy_user.php?tp=bts1/docs/proxy.html', 'label' => 'Différences entre Proxy et Proxy inverse'],
                        ],
                    ],
                    'Monitoring' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/monitoring/monitoring.php', 'label' => 'Monitoring serveur'],
                        ],
                    ],
                    'SIEM' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/siem/index.php', 'label' => 'Mini-SIEM pédagogique'],
                        ],
                    ],
                    'Statistiques' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/dashboard.php', 'label' => 'Dashboard DokuWiki'],
                        ],
                    ],
                    'Supervision' => [
                        'items' => [
                            ['url' => 'https://stats.uptimerobot.com/0YjmpB2T6m', 'label' => 'Supervision globale'],
                            ['url' => 'https://cours-reseaux.fr/nagios/public/login.php', 'label' => 'Supervision DokuWiki'],
                        ],
                    ],
                ],
            ],
            'Aide à l\'utilisation du wiki' => [
                'subcategories' => [
                    'Ancien site' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/doc:site_bts1', 'label' => 'Identifiants du site privé'],
                        ],
                    ],
                    'Assistance' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/doc:mot_de_passe', 'label' => 'Changement de mot de passe'],
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/cloud:menu', 'label' => 'Recherche par mots clés'],
                        ],
                    ],
                    'Envoi de travaux' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/etudiants:menu', 'label' => 'Envoi des TP et devoirs'],
                        ],
                    ],
                    'RGPD' => [
                        'items' => [
                            ['url' => 'https://cours-reseaux.fr/bts_sio/doku.php/doc:rgpd:menu', 'label' => 'Politique de confidentialité'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
