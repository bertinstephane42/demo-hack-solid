# cours-reseaux.fr — Architecture Laravel from scratch

Réécriture complète de l'application pédagogique cours-reseaux.fr suivant les principes **SOLID**, les **Design Patterns** et l'architecture du framework **Laravel**, sans dépendances externes (aucun Composer, aucun framework tiers côté PHP — Bootstrap 5 est chargé en CDN pour le rendu).

## Architecture

```
.
├── .htaccess                    # Apache : HTTPS forcé, protection des dossiers/fichiers sensibles, FallbackResource
├── helpers.php                  # Fonctions globales (env, config, route, view, app, asset, CSRF, journal contact)
├── bootstrap/
│   └── app.php                  # Bootstrapping : charge les classes, crée l'Application, enregistre les providers
├── core/                        # Framework kernel (zéro dépendance)
│   ├── Application.php          # Application (hérite du Container) : basePath, providers, run()
│   ├── Container.php            # Service Container (DI, singletons, résolution par réflexion)
│   ├── Facade.php               # Accès statique aux services du conteneur (pattern Facade)
│   ├── Request.php              # Requête HTTP (query, body, headers, JSON, IP, …)
│   ├── Response.php             # Fabrique de réponses (html, json, redirect)
│   ├── Route.php                # Définition d'une route
│   ├── Router.php               # Routeur (groupes, middleware, alias, dispatch)
│   └── View.php                 # Moteur de templates (layouts, @section/@yield, partials)
├── app/
│   ├── Http/
│   │   ├── Controllers/         # Contrôleurs MVC (Home, Contact, Sitemap, Api, Admin, Proxy)
│   │   ├── Middleware/          # Chain of responsibility (SecurityHeaders, RateLimit)
│   │   └── Kernel.php           # Pipeline de middleware global
│   ├── Models/                  # Modèles de données (SitemapMenu — dépôt en mémoire)
│   ├── Providers/               # Service Providers (App, Route, View)
│   └── Services/                # Services métier (Auth, Mailer, LogReader, …)
├── config/                      # Configuration par fichier (app, admin, mail, rate, security, turnstile)
├── routes/
│   └── web.php                  # Toutes les routes (publiques + administration)
├── resources/views/             # Templates Blade-like (layouts/, admin/, partials/, pages publiques)
├── public/                      # Document root : index.php + assets (css, js, img)
└── storage/                     # Dossier protégé : logs/ (journaux), tmp/ (rate limiting)
```

## Design Patterns

| Pattern | Utilisation |
|---|---|
| **Front Controller** | `public/index.php` — point d'entrée unique (détection du basePath + bootstrap) |
| **Service Container (DI)** | `core/Container.php` — résolution automatique par réflexion, singletons |
| **Singleton** | Container, Application, services partagés (Auth, Mailer, View, …) |
| **Facade** | `core/Facade.php` — accès statique aux services du conteneur |
| **Middleware** | Chain of Responsibility pour le pipeline HTTP (global + par route) |
| **MVC** | Séparation Controllers / Views / Models |
| **Repository** | `SitemapMenu` — accès aux données du plan du site (tableau en mémoire) |
| **Strategy** | Tous les Middleware implémentent l'interface `Middleware::handle()` |
| **Template Method** | `View.php` — rendu avec layouts et partials |
| **Factory** | `Response::make()`, `Response::json()`, `Response::redirect()` |

## SOLID

- **S**RP — Un contrôleur par page, un service par responsabilité
- **O**CP — Middleware ajoutables sans modifier le Kernel
- **L**SP — Tous les Middleware implémentent `Middleware::handle()`
- **I**SP — Interfaces ciblées, pas d'interfaces géantes
- **D**IP — Controllers dépendent d'abstractions injectées

## Configuration

L'application fonctionne sans `.env` : toutes les variables possèdent des valeurs par défaut dans `config/`. Un fichier `.env`, s'il est présent à la racine, n'est jamais accessible via le web (bloqué par le `.htaccess` racine).

Fichiers de configuration :

| Fichier | Contenu |
|---|---|
| `config/app.php` | Nom, environnement, debug, URL, liste des providers |
| `config/admin.php` | Compte administrateur (`admin_email` + `password_hash` bcrypt) |
| `config/mail.php` | Expéditeur, destinataire, logs de débogage, quotas d'envoi |
| `config/rate.php` | Limitation de débit par IP (max par fenêtre) |
| `config/security.php` | En-têtes de sécurité et politique CSP |
| `config/turnstile.php` | Clés Cloudflare Turnstile (`TURNSTILE_SITEKEY` / `TURNSTILE_SECRET`) |

Variables d'environnement notables : `MAIL_FROM`, `MAIL_TO`, `MAIL_LOG_ENABLED`, `RATE_LIMIT_MAX`, `RATE_LIMIT_WINDOW`, `CSP_*`, `APP_DEBUG`, `APP_ENV`, `TURNSTILE_SITEKEY`, `TURNSTILE_SECRET`.

### Cloudflare Turnstile (anti-robots du formulaire de contact)

Le formulaire de contact utilise **Cloudflare Turnstile** lorsque les clés sont
configurées, avec repli automatique sur le calcul arithmétique si l'API est
injoignable. Tant que les deux clés sont absentes/vides, le comportement reste
identique à l'ancien (calcul arithmétique uniquement).

Activation :

1. Créer un widget Turnstile sur le dashboard Cloudflare (Type « Managed »).
   Autoriser les noms d'hôtes `cours-reseaux.fr`, `www.cours-reseaux.fr`,
   `localhost` et `127.0.0.1`.
2. Renseigner dans `.env` (racine du site, invisible depuis le web) :
   `TURNSTILE_SITEKEY=0x4AAAA…` et `TURNSTILE_SECRET=0x4AAAA…`.
3. Vider la mémoire de PHP (le service lit la config au démarrage) si un cache
   d'opcode type OPcache est actif.
4. La CSP ajoute `https://challenges.cloudflare.com` à `script-src`
   automatiquement (`config/security.php`) ; sinon, sauf si `.env` surcharge
   `CSP_SCRIPT_SRC` sans cette origine.

Déploiement :

1. Placer la document root Apache sur `public/` (ou s'appuyer sur le `FallbackResource /public/index.php` du `.htaccess` racine pour un hébergement mutualisé).
2. Le compte administrateur se configure dans `config/admin.php` :
   `password_hash('VotreMotDePasse', PASSWORD_BCRYPT)`.
3. Les assets CSS/JS sont servis statiquement depuis `public/`.

## Sécurité

- `.htaccess` racine : HTTPS forcé (301), rejet de `app/`, `bootstrap/`, `config/`, `core/`, `resources/`, `routes/`, `storage/`, `helpers.php`, `.env` et des fichiers `.log` / `.md` / `.sql`.
- `storage/.htaccess` : refus de tout accès web (les journaux ne sont jamais téléchargeables).
- En-têtes de sécurité + `Content-Security-Policy` (via `config/security.php`).
- CSRF sur toutes les actions POST de l'administration (`csrf_token()` / `csrf_field()`).
- Limitation de débit par IP (POST uniquement), quota verrouillé par fichier dans `storage/tmp/`.
- Formulaire de contact : jeton de session, minuterie anti-soumission rapide, honeypot, Cloudflare Turnstile (ou calcul anti-robots de repli quand l'API est injoignable), validation stricte des champs.
- Mots de passe stockés en bcrypt ; politique stricte imposée côté administration.

## Routes

### Routes publiques

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/` | HomeController@index | Page d'accueil |
| GET | `/contact` | ContactController@show | Formulaire de contact |
| POST | `/contact` | ContactController@submit | Envoi du formulaire |
| GET | `/sitemap` | SitemapController@index | Plan du site |
| GET | `/api/data` | ApiController@modalData | Données pour la modale (AJAX) |

### Administration

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/admin` | AdminController@showLogin | Formulaire de connexion |
| POST | `/admin` | AdminController@login | Connexion |
| GET | `/admin/login` | AdminController@showLogin | Alias rétro-compatible |
| GET | `/admin/forgot` | AdminController@forgot | Demande de code de réinitialisation |
| POST | `/admin/forgot` | AdminController@doForgot | Envoi du code par e-mail |
| GET | `/admin/reset` | AdminController@reset | Saisie du code + nouveau mot de passe |
| POST | `/admin/reset` | AdminController@doReset | Réinitialisation effective |
| POST | `/admin/logout` | AdminController@logout | Déconnexion (CSRF) |
| GET | `/admin/dashboard` | AdminController@dashboard | Tableau de bord |
| GET | `/admin/mail` | AdminController@mail | Configuration des e-mails |
| POST | `/admin/mail` | AdminController@doMail | Enregistrement de la configuration |
| POST | `/admin/mail/test` | AdminController@testMail | Test d'envoi |
| GET | `/admin/password` | AdminController@password | Changement du mot de passe |
| POST | `/admin/password` | AdminController@doPassword | Enregistrement |
| GET | `/admin/user` | AdminController@user | E-mail de connexion |
| POST | `/admin/user` | AdminController@doUser | Enregistrement |
| GET | `/admin/logs` | AdminController@logs | **Journaux** (onglets connexions / messages de contact) |
| POST | `/admin/logs/clear` | AdminController@clearLogs | Vidage du journal actif |
| GET | `/admin/export` | AdminController@export | Sauvegarde (export JSON) |
| POST | `/admin/export/download` | AdminController@downloadExport | Téléchargement de la sauvegarde |
| GET | `/admin/system` | AdminController@system | Diagnostic système |
| POST | `/admin/system/purge-tmp` | AdminController@purgeTmp | Purge de `storage/tmp/` |

Les URLs de destination sont centralisées dans le tableau `route()` de `helpers.php` (équivalent du nommage de routes Laravel).

## Journaux (module « Maintenance » → « Journaux »)

L'administration expose une section **Journaux** (bouton « Journaux », dépend de l'authentification) qui présente deux onglets : **Journal de connexion** et **Messages de contact**.

- **Journal de connexion** — `storage/logs/login.log` : chaque tentative de connexion/réinitialisation à l'administration (connexions réussies / échecs et blocages), avec horodatage, adresse IP, e-mail et résultat.
- **Messages de contact** — `storage/logs/contact.log` : une ligne JSON par soumission du formulaire de contact (nom, e-mail, message, copie, résultat d'envoi, erreur), quelles que soient les lignes de débogage technique.

Chaque onglet affiche les statistiques (réussite / échec, envois / échecs), une table paginée par 25 et un bouton « Vider ce journal ». Un **double-clic sur une ligne du journal des messages de contact** ouvre une modale présentant le message complet (bulle d'information sur l'en-tête de la colonne « Message »).

La lecture est assurée par `app/Services/LogReader.php` : fenêtre de lecture plafonnée à la fin du fichier (2 Mo), entrées triées de la plus récente à la plus ancienne, compteurs par onglet. Tous les journaux vivent dans `storage/logs/`, dossier inaccessible depuis le web.

## Services

| Service | Responsabilité |
|---|---|
| `Validator` | Validation des champs du formulaire de contact |
| `Auth` | Authentification admin, verrous anti-brute-force, journalisation des connexions |
| `PasswordReset` | Codes de réinitialisation temporaires et expiration |
| `Mailer` | Envoi de mails (PHP `mail()` ou SMTP), quotas, journalisation |
| `SmtpTransport` | Transport SMTP (Brevo) |
| `MailConfig` | Lecture/écriture de la configuration e-mail |
| `SystemCheck` | Diagnostic système (dossiers, fichiers, mail) |
| `DataExporter` | Sauvegarde de la configuration en JSON |
| `LogReader` | Lecture des journaux (login + contact) pour l'administration |
| `Turnstile` | Vérification du captcha Cloudflare (tri-état ok / invalide / API injoignable, bascule calcul arithmétique) |
| `SitemapMenu` (Model) | Arborescence du plan du site |

## Comparaison avant/après

| Métrique | Avant | Après |
|---|---|---|
| Fichiers PHP | 4 (monolithiques) | 40+ (séparés) |
| Lignes dans index.php | 312 | 60 (détection du basePath + bootstrap) |
| Séparation logique/présentation | Mélangé | MVC avec layouts et partials |
| Testabilité | Impossible | Controllers et services testables (DI) |
| Réutilisabilité | Code dupliqué | Partials, Services, Containers |
| Configuration | Hardcoded | `config/` + `.env` optionnel |
| Sécurité | Manuelle | Middleware pipeline, CSP, CSRF, rate limiting |