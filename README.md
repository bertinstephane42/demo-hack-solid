# cours-reseaux.fr — Architecture Laravel from scratch

Réécriture complète de l'application pédagogique cours-reseaux.fr suivant les principes **SOLID**, les **Design Patterns** et l'architecture du framework **Laravel**, sans dépendances externes.

## Architecture

```
├── core/                    # Framework kernel
│   ├── Container.php        # Service Container (DI)
│   ├── Application.php      # Application principale
│   ├── Request.php          # HTTP Request
│   ├── Response.php         # HTTP Response factory
│   ├── Router.php           # Routeur avec groupes
│   ├── Route.php            # Définition de route
│   └── View.php             # Moteur de templates
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Contrôleurs MVC
│   │   ├── Middleware/      # Middleware pipeline
│   │   └── Kernel.php       # HTTP Kernel
│   ├── Models/              # Modèles de données
│   ├── Providers/           # Service Providers
│   └── Services/            # Services métier
├── config/                  # Configuration
├── routes/                  # Définition des routes
├── resources/views/         # Templates Blade-like
├── public/                  # Point d'entrée + assets
├── storage/                 # Logs, cache
└── bootstrap/app.php        # Bootstrapping
```

## Design Patterns

| Pattern | Utilisation |
|---|---|
| **Front Controller** | `public/index.php` — point d'entrée unique |
| **Dependency Injection** | `Container.php` — résolution automatique par réflexion |
| **Singleton** | Container, Application, services partagés |
| **Middleware** | Chain of Responsibility pour le pipeline HTTP |
| **MVC** | Séparation Controllers / Views / Models |
| **Repository** | `SitemapMenu` — accès aux données du plan du site |
| **Strategy** | Chaque Middleware implémente la même interface |
| **Template Method** | `View.php` — rendu avec layouts et partials |
| **Factory** | `Response::json()`, `Response::redirect()` |

## SOLID

- **S**RP — Un contrôleur par page, un service par responsabilité
- **O**CP — Middleware ajoutables sans modifier le Kernel
- **L**SP — Tous les Middleware implémentent `Middleware::handle()`
- **I**SP — Interfaces ciblées, pas d'interfaces géantes
- **D**IP — Controllers dépendent d'abstractions injectées

## Configuration

1. Copier `.env.example` vers `.env`
2. Configurer le vhost Apache pour pointer vers `public/`
3. Les assets CSS/JS sont servis statiquement depuis `public/`

## Routes

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/` | HomeController@index | Page d'accueil |
| GET | `/contact` | ContactController@show | Formulaire de contact |
| POST | `/contact` | ContactController@submit | Envoi du formulaire |
| GET | `/sitemap` | SitemapController@index | Plan du site |
| GET | `/api/data` | ApiController@modalData | Données modales (AJAX) |

## Comparaison avant/après

| Métrique | Avant | Après |
|---|---|---|
| Fichiers PHP | 4 (monolithiques) | 25+ (séparés) |
| Lignes dans index.php | 312 | 3 |
| Séparation logique/présentation | ❌ Mélangé | ✅ MVC |
| Testabilité | ❌ Impossible | ✅ Controllers testables |
| Réutilisabilité | ❌ Code dupliqué | ✅ Partials, Services |
| Configuration | ❌ Hardcoded | ✅ .env + config/ |
| Sécurité | ⚠️ Manuelle | ✅ Middleware pipeline |
