# 🛠️ GUIDE D'IMPLÉMENTATION SYMFONY - SEO

## Exemples de code pratiques pour Symfony

---

## 1. Configuration des services SEO

### Créer un service de gestion des métadonnées

**Fichier :** `src/Service/SeoService.php`

```php
<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SeoService
{
    private string $siteName = 'BTS Fulbert - Inscription';
    private string $siteUrl;
    private string $defaultImage;

    public function __construct(
        private ParameterBagInterface $params
    ) {
        $this->siteUrl = $params->get('app.domain');
        $this->defaultImage = $this->siteUrl . '/images/og-image.png';
    }

    /**
     * Génère les métadonnées complètes pour une page
     */
    public function generateMeta(
        string $title,
        string $description,
        ?string $image = null,
        ?string $url = null
    ): array {
        return [
            'title' => $this->sanitizeTitle($title),
            'description' => $this->sanitizeDescription($description),
            'image' => $image ?? $this->defaultImage,
            'url' => $url ?? $this->siteUrl,
            'og' => [
                'title' => $this->sanitizeTitle($title),
                'description' => $this->sanitizeDescription($description),
                'image' => $image ?? $this->defaultImage,
                'type' => 'website',
                'site_name' => $this->siteName,
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $this->sanitizeTitle($title),
                'description' => $this->sanitizeDescription($description),
                'image' => $image ?? $this->defaultImage,
            ]
        ];
    }

    private function sanitizeTitle(string $title): string
    {
        $title = strip_tags($title);
        return strlen($title) > 60 ? substr($title, 0, 57) . '...' : $title;
    }

    private function sanitizeDescription(string $description): string
    {
        $description = strip_tags($description);
        $description = preg_replace('/\s+/', ' ', $description);
        return strlen($description) > 160 ? substr($description, 0, 157) . '...' : $description;
    }

    /**
     * Génère une URL canonique
     */
    public function getCanonicalUrl(string $route, array $params = []): string
    {
        return $this->siteUrl . $this->generateUrl($route, $params);
    }

    private function generateUrl(string $route, array $params): string
    {
        // À adapter selon votre système d'URL
        return str_replace('{id}', $params['id'] ?? '', $route);
    }
}
```

### Enregistrer le service

**Fichier :** `config/services.yaml`

```yaml
services:
    App\Service\SeoService:
        arguments:
            - '@parameter_bag'
        tags:
            - { name: 'twig.global', key: 'seoService' }
```

### Configurer les paramètres

**Fichier :** `config/services.yaml` (ou `.env`)

```yaml
parameters:
    app.domain: 'https://votre-domaine.com'
    app.site_name: 'BTS Fulbert - Inscription'
```

---

## 2. Template de base amélioré

### Fichier : `templates/base.html.twig`

```twig
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    {# SEO Meta Tags #}
    <title>{% block title %}BTS Fulbert{% endblock %}</title>
    {% block meta_tags %}
        <meta name="description" content="{% block description %}Plateforme d'inscription en ligne pour formations BTS{% endblock %}">
        <meta name="keywords" content="BTS, inscription, candidature, formation">
        <meta name="author" content="BTS Fulbert">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="theme-color" content="#003366">
    {% endblock %}

    {# Canonical URL #}
    <link rel="canonical" href="{% block canonical %}{{ app.request.uri }}{% endblock %}">

    {# Open Graph Tags #}
    <meta property="og:type" content="{% block og_type %}website{% endblock %}">
    <meta property="og:title" content="{% block og_title %}{{ block('title') }}{% endblock %}">
    <meta property="og:description" content="{% block og_description %}{{ block('description') }}{% endblock %}">
    <meta property="og:image" content="{% block og_image %}{{ absolute_url('/images/og-image.png') }}{% endblock %}">
    <meta property="og:url" content="{% block og_url %}{{ app.request.uri }}{% endblock %}">
    <meta property="og:site_name" content="BTS Fulbert">

    {# Twitter Card Tags #}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{% block twitter_title %}{{ block('title') }}{% endblock %}">
    <meta name="twitter:description" content="{% block twitter_description %}{{ block('description') }}{% endblock %}">
    <meta name="twitter:image" content="{% block twitter_image %}{{ block('og_image') }}{% endblock %}">

    {# Favicon #}
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    {# Preconnect & Font Loading #}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" 
          rel="stylesheet"
          media="print"
          onload="this.media='all'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" 
              rel="stylesheet">
    </noscript>

    {% block stylesheets %}
        {{ encore_entry_link_tags('app') }}
    {% endblock %}

    {# JSON-LD Schema #}
    {% block schema_org %}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": "BTS Fulbert",
        "description": "Plateforme d'inscription en ligne pour formations BTS",
        "url": "{{ absolute_url('/') }}",
        "logo": "{{ absolute_url('/images/logo.png') }}",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+33-X-XX-XX-XX-XX",
            "contactType": "Customer Service"
        },
        "address": {
            "@type": "PostalAddress",
            "addressCountry": "FR",
            "addressLocality": "Votre Ville",
            "postalCode": "75000"
        }
    }
    </script>
    {% endblock %}
</head>

<body>
    {# Header #}
    <div class="hero" role="banner">
        <div class="container">
            <h1>BTS Fulbert</h1>
            <p>Plateforme d'inscription en ligne</p>
        </div>
    </div>

    {# Navigation #}
    <nav class="navbar" aria-label="Navigation principale">
        <div class="container">
            <a href="{{ path('app_home') }}">Accueil</a>
            <a href="{{ path('app_dashboard') }}">Dashboard</a>
            {% if app.user %}
                <a href="{{ path('app_logout') }}">Déconnexion</a>
            {% else %}
                <a href="{{ path('app_login') }}">Connexion</a>
            {% endif %}
        </div>
    </nav>

    {# Breadcrumb #}
    {% block breadcrumb %}{% endblock %}

    {# Main Content #}
    <main id="main-content" role="main">
        <div class="container">
            {% block body %}{% endblock %}
        </div>
    </main>

    {# Footer #}
    <footer role="contentinfo" aria-label="Pied de page">
        <div class="container">
            <p>&copy; {{ "now"|date("Y") }} BTS Fulbert. Tous droits réservés.</p>
            <nav aria-label="Navigation secondaire">
                <a href="#">Politique de confidentialité</a>
                <a href="#">Conditions d'utilisation</a>
                <a href="#">Mentions légales</a>
                <a href="#">Contact</a>
            </nav>
        </div>
    </footer>

    {% block javascripts %}
        {{ encore_entry_script_tags('app') }}
    {% endblock %}
</body>
</html>
```

---

## 3. Template d'étape inscription optimisé

### Fichier : `templates/inscription/step1.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block title %}Informations Personnelles - Inscription BTS | Étape 1{% endblock %}

{% block description %}Remplissez vos informations personnelles pour débuter votre inscription BTS. Saisie sécurisée avec validation automatique. Sauvegarde continue de vos données.{% endblock %}

{% block og_title %}Inscription BTS - Étape 1 : Informations Personnelles{% endblock %}
{% block og_description %}Commencez votre inscription BTS en remplissant vos informations personnelles. Formulaire rapide et sécurisé.{% endblock %}

{% block breadcrumb %}
<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="Fil d'Ariane">
            <ol itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ path('app_dashboard') }}">
                        <span itemprop="name">🏠 Accueil</span>
                    </a>
                    <meta itemprop="position" content="1">
                </li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="{{ path('inscription_step1') }}">
                        <span itemprop="name">📋 Mon dossier</span>
                    </a>
                    <meta itemprop="position" content="2">
                </li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name" aria-current="page">👤 Informations Personnelles</span>
                    <meta itemprop="position" content="3">
                </li>
            </ol>
        </nav>
    </div>
</div>
{% endblock %}

{% block body %}
<article class="inscription-form" itemscope itemtype="https://schema.org/Form">
    <div class="container" style="max-width:760px;">
        {# Indicateur de progression #}
        {{ stepper(step, dossier.etapeActuelle) }}

        <section class="card">
            <header class="card__head">
                <h1 id="page-title">Étape 1 — Informations Personnelles</h1>
                <p class="subtitle" aria-describedby="page-title">
                    Remplissez vos informations de base pour débuter votre dossier
                </p>
                <p class="info-text">
                    <strong aria-label="requis">*</strong> = Champ obligatoire  
                    💾 Sauvegarde automatique
                </p>
            </header>

            {{ form_start(form, {attr: {novalidate: 'novalidate'}}) }}

            <div class="card__body">
                <div class="info-box" role="status" aria-live="polite">
                    ℹ️ Tous vos changements sont sauvegardés automatiquement.
                </div>

                {# Section Identité #}
                <section class="form-section">
                    <h2>Identité</h2>
                    
                    <fieldset>
                        <legend>Civilité</legend>
                        <div class="form-group">
                            <div class="radio-row">
                                {% for child in form.genre %}
                                    <label>
                                        {{ form_widget(child) }}
                                        <span>{{ child.vars.label }}</span>
                                    </label>
                                {% endfor %}
                            </div>
                            {% if form_errors(form.genre) %}
                                <div role="alert" aria-live="assertive">
                                    {{ form_errors(form.genre) }}
                                </div>
                            {% endif %}
                        </div>
                    </fieldset>

                    {# Champ Prénom #}
                    <div class="form-group">
                        <label for="form_prenom">
                            Prénom 
                            <span aria-label="requis">*</span>
                        </label>
                        {{ form_widget(form.prenom, {
                            attr: {
                                'id': 'form_prenom',
                                'placeholder': 'Votre prénom',
                                'aria-required': 'true',
                                'aria-describedby': 'form_prenom_help'
                            }
                        }) }}
                        <small id="form_prenom_help">Votre prénom tel qu'il apparaît sur votre pièce d'identité</small>
                        {% if form_errors(form.prenom) %}
                            <div id="form_prenom_error" role="alert" aria-live="polite">
                                {{ form_errors(form.prenom) }}
                            </div>
                        {% endif %}
                    </div>

                    {# Champ Nom #}
                    <div class="form-group">
                        <label for="form_nom">
                            Nom 
                            <span aria-label="requis">*</span>
                        </label>
                        {{ form_widget(form.nom, {
                            attr: {
                                'id': 'form_nom',
                                'placeholder': 'Votre nom',
                                'aria-required': 'true',
                                'aria-describedby': 'form_nom_help'
                            }
                        }) }}
                        <small id="form_nom_help">Votre nom de famille</small>
                        {% if form_errors(form.nom) %}
                            <div id="form_nom_error" role="alert" aria-live="polite">
                                {{ form_errors(form.nom) }}
                            </div>
                        {% endif %}
                    </div>
                </section>

                {# Boutons #}
                <div class="form-actions">
                    <button type="submit" name="action" value="next" class="btn btn-primary">
                        Continuer vers l'étape 2 →
                    </button>
                    <button type="submit" name="action" value="save" class="btn btn-secondary">
                        Enregistrer et quitter
                    </button>
                </div>
            </div>

            {{ form_end(form) }}
        </section>
    </div>
</article>
{% endblock %}

{% block schema_org %}
    {{ parent() }}
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Accueil",
                "item": "{{ absolute_url(path('app_dashboard')) }}"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Mon dossier",
                "item": "{{ absolute_url(path('inscription_step1')) }}"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "Informations Personnelles",
                "item": "{{ absolute_url(path('inscription_step1')) }}"
            }
        ]
    }
    </script>
{% endblock %}
```

---

## 4. Créer les fichiers statiques SEO

### Fichier : `public/robots.txt`

```
User-agent: *
Allow: /
Allow: /assets/
Allow: /images/

Disallow: /admin/
Disallow: /api/private/
Disallow: /tmp/
Disallow: /*.json$
Disallow: /config/

# Ralentissement du crawl pour bing
User-agent: Bingbot
Crawl-delay: 2

# Ralentissement du crawl pour yahoo
User-agent: Slurp
Crawl-delay: 3

# Sitemap
Sitemap: https://votre-domaine.com/sitemap.xml
Sitemap: https://votre-domaine.com/sitemap-index.xml
```

### Fichier : `public/site.webmanifest`

```json
{
    "name": "BTS Fulbert - Inscription",
    "short_name": "BTS Fulbert",
    "description": "Plateforme d'inscription en ligne pour formations BTS",
    "start_url": "/",
    "scope": "/",
    "display": "standalone",
    "orientation": "portrait-primary",
    "background_color": "#ffffff",
    "theme_color": "#003366",
    "icons": [
        {
            "src": "/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any maskable"
        }
    ],
    "categories": ["education"],
    "screenshots": [
        {
            "src": "/screenshot-1.png",
            "sizes": "540x720",
            "type": "image/png"
        }
    ]
}
```

---

## 5. Générer le sitemap avec tâche Symfony

### Fichier : `src/Command/GenerateSitemapCommand.php`

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route;

#[AsCommand(
    name: 'app:sitemap:generate',
    description: 'Generate sitemap.xml for SEO'
)]
class GenerateSitemapCommand extends Command
{
    public function __construct(
        private RouterInterface $router,
        private string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baseUrl = 'https://votre-domaine.com';
        $publicPath = $this->projectDir . '/public';
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Routes à inclure
        $routes = $this->router->getRouteCollection();
        $excludedRoutes = ['_wdt', '_profiler', 'api', 'admin'];

        foreach ($routes as $name => $route) {
            // Exclure certaines routes
            if ($this->shouldExclude($name, $excludedRoutes)) {
                continue;
            }

            $path = $route->getPath();
            
            // Remplacer les paramètres par des valeurs par défaut
            $path = preg_replace('/\{(\w+)\}/', '1', $path);

            $url = $baseUrl . $path;
            $priority = $this->getPriority($name);
            $changefreq = $this->getChangeFreq($name);

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <changefreq>" . $changefreq . "</changefreq>\n";
            $xml .= "    <priority>" . $priority . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        // Écrire le fichier
        file_put_contents($publicPath . '/sitemap.xml', $xml);
        
        $output->writeln('✓ Sitemap généré : ' . $publicPath . '/sitemap.xml');

        return Command::SUCCESS;
    }

    private function shouldExclude(string $routeName, array $excluded): bool
    {
        foreach ($excluded as $pattern) {
            if (strpos($routeName, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getPriority(string $routeName): string
    {
        if (in_array($routeName, ['app_home', 'app_dashboard'])) {
            return '1.0';
        }
        if (strpos($routeName, 'inscription') !== false) {
            return '0.8';
        }
        return '0.5';
    }

    private function getChangeFreq(string $routeName): string
    {
        if (strpos($routeName, 'admin') !== false) {
            return 'never';
        }
        if (in_array($routeName, ['app_home'])) {
            return 'weekly';
        }
        return 'monthly';
    }
}
```

**Utilisation :**
```bash
php bin/console app:sitemap:generate
```

---

## 6. Configuration de sécurité (Security Headers)

### Fichier : `config/packages/security.yaml`

```yaml
framework:
    session:
        name: 'PHPSESSID_SECURE'
        secure: true
        httponly: true
        samesite: strict
        cookie_lifetime: 0

security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
            algorithm: auto

# Headers de sécurité via Apache/Nginx
# À configurer dans .htaccess ou nginx.conf
```

---

## 7. Tests et validation

### Commandes pour tester le SEO

```bash
# Générer le sitemap
php bin/console app:sitemap:generate

# Valider le schema JSON-LD
curl -X POST https://validator.schema.org/ -d @sitemap.xml

# Vérifier les erreurs
php bin/console lint:twig
php bin/console lint:yaml
```

---

## Résumé des fichiers à modifier/créer

| Fichier | Action | Priorité |
|---------|--------|----------|
| `templates/base.html.twig` | Modifier | 🔴 Critique |
| `templates/inscription/*.html.twig` | Modifier | 🔴 Critique |
| `src/Service/SeoService.php` | Créer | 🟠 Haute |
| `config/services.yaml` | Modifier | 🟠 Haute |
| `public/robots.txt` | Créer | 🔴 Critique |
| `public/sitemap.xml` | Créer | 🔴 Critique |
| `public/site.webmanifest` | Créer | 🟡 Moyenne |
| `src/Command/GenerateSitemapCommand.php` | Créer | 🟡 Moyenne |

---

