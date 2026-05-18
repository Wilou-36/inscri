# 📋 CHECKLIST D'IMPLÉMENTATION SEO

## Phase 1 : Fondamentaux (Priorité Critique)

### 1. ✅ Ajouter Viewport Meta Tag
**Fichier :** `templates/base.html.twig`  
**Ajout dans `<head>` :**
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

### 2. ✅ Créer Robots.txt
**Fichier :** `public/robots.txt`
```
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/private/

Sitemap: https://votre-domaine.com/sitemap.xml
```

### 3. ✅ Créer Sitemap.xml (partiellement)
**Fichier :** `public/sitemap.xml`

À générer automatiquement ou manuellement pour les pages statiques.

### 4. ✅ Ajouter Meta Descriptions
**Fichier :** `templates/base.html.twig`
**Dans le bloc `<head>` :**
```twig
{% block meta_description %}
<meta name="description" content="{% block description %}Plateforme d'inscription en ligne pour BTS Fulbert{% endblock %}">
{% endblock %}
```

**Puis dans chaque template enfant :**
```twig
{% block description %}Postulez pour votre BTS en ligne. Inscription complète en 5 étapes simples...{% endblock %}
```

### 5. ✅ Ajouter Canonical Tags
**Fichier :** `templates/base.html.twig`
```twig
<link rel="canonical" href="{{ absolute_url(path(app.request.attributes.get('_route'), app.request.attributes.get('_route_params'))) }}">
```

---

## Phase 2 : Structure & Données (Priorité Haute)

### 6. ✅ Ajouter Favicon
**Fichier :** `templates/base.html.twig`
```html
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
```

### 7. ✅ Implémenter JSON-LD
**Fichier :** `templates/base.html.twig`
```twig
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "BTS Fulbert",
  "description": "Plateforme d'inscription en ligne pour formations BTS",
  "url": "{{ absolute_url('/') }}",
  "logo": "{{ absolute_url('/images/logo.png') }}",
  "address": {
    "@type": "PostalAddress",
    "addressCountry": "FR"
  }
}
</script>
```

### 8. ✅ Ajouter BreadcrumbList JSON-LD
**Fichier :** `templates/inscription/step1.html.twig` (et autres)
```twig
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Accueil",
      "item": "{{ absolute_url(path('app_home')) }}"
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
```

### 9. ✅ Optimiser Title Tags
**Fichier :** Tous les templates enfants

```twig
{% block title %}Inscription BTS Fulbert | Postulez en 5 étapes{% endblock %}
```

### 10. ✅ Ajouter Open Graph Tags
**Fichier :** `templates/base.html.twig`
```html
<meta property="og:type" content="website">
<meta property="og:title" content="{% block og_title %}{{ block('title') }}{% endblock %}">
<meta property="og:description" content="{% block og_description %}{{ block('description') }}{% endblock %}">
<meta property="og:image" content="{% block og_image %}{{ absolute_url('/images/og-image.png') }}{% endblock %}">
<meta property="og:url" content="{{ absolute_url(path(app.request.attributes.get('_route'), app.request.attributes.get('_route_params'))) }}">
<meta property="og:site_name" content="BTS Fulbert - Inscription">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{% block twitter_title %}{{ block('title') }}{% endblock %}">
<meta name="twitter:description" content="{% block twitter_description %}{{ block('description') }}{% endblock %}">
<meta name="twitter:image" content="{% block twitter_image %}{{ absolute_url('/images/og-image.png') }}{% endblock %}">
```

---

## Phase 3 : Accessibilité & Performance

### 11. ✅ Améliorer Accessibilité (ARIA)
**Fichiers :** Templates de formulaires

```html
<!-- Formulaires -->
<fieldset>
    <legend>Identité</legend>
    <!-- ... champs avec aria-labels ... -->
</fieldset>

<!-- Labels -->
<label for="prenom">Prénom <span aria-label="requis">*</span></label>
<input id="prenom" aria-required="true" aria-describedby="prenom-help">
<small id="prenom-help">Votre prénom tel qu'il apparaît sur votre pièce d'identité</small>

<!-- Erreurs -->
<div id="error-prenom" role="alert" aria-live="polite">
    Ce champ est obligatoire
</div>

<!-- Navigation -->
<nav aria-label="Navigation principale">
```

### 12. ✅ Ajouter Preload/Preconnect
**Fichier :** `templates/base.html.twig`
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
```

### 13. ✅ Optimiser Images
**Directive :** Utiliser `loading="lazy"` et `decoding="async"`
```html
<img src="..." alt="Description" loading="lazy" decoding="async">
```

### 14. ✅ Minifier CSS/JS
**Configuration Webpack :** À vérifier dans `webpack.config.js`

### 15. ✅ Cache Headers
**Configuration Apache/Nginx :** À mettre en place

---

## Fichiers à créer/modifier

### À CRÉER

```
public/
├── robots.txt              ← NOUVEAU
├── sitemap.xml             ← NOUVEAU (ou générer automatiquement)
├── favicon.ico             ← NOUVEAU
├── apple-touch-icon.png    ← NOUVEAU
└── site.webmanifest        ← NOUVEAU

images/
├── og-image.png            ← NOUVEAU (1200x630px)
└── hero-optimized.webp     ← NOUVEAU
```

### À MODIFIER

```
templates/
├── base.html.twig          ← Métadonnées + Schema + ARIA
├── inscription/
│   ├── step1.html.twig     ← Descriptions + Breadcrumbs
│   ├── step2.html.twig     ← Descriptions
│   ├── step3.html.twig     ← Descriptions
│   ├── step4.html.twig     ← Descriptions
│   └── step5.html.twig     ← Descriptions
├── dashboard/
│   └── index.html.twig     ← Descriptions
└── security/
    ├── login.html.twig     ← Descriptions
    └── register.html.twig  ← Descriptions

src/
└── Controller/
    └── InscriptionController.php  ← À analyser
```

---

## Validation & Tests

### Tools à utiliser

- [ ] Google Search Console
- [ ] Google PageSpeed Insights
- [ ] GTmetrix
- [ ] Wave (Accessibilité)
- [ ] Lighthouse (Chrome DevTools)
- [ ] Schema.org Validator
- [ ] Mobile-Friendly Test

### Métriques à suivre

- [ ] Impressions sur Google
- [ ] CTR (Click-Through Rate)
- [ ] Core Web Vitals
- [ ] Accessibilité (WCAG AA)
- [ ] Temps de chargement

---

## Notes de sécurité

### Headers HTTP à vérifier

```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=()
```

### Configuration Symfony (security.yaml)

```yaml
framework:
    session:
        name: PHPSESSID_SECURE
        secure: true
        httponly: true
        samesite: strict
```

---

## Support & Documentation

### Documentation officielle
- https://developers.google.com/search
- https://moz.com/beginners-guide-to-seo
- https://www.w3.org/WAI/
- https://symfony.com/doc/

### Outils
- Google Search Console
- Google Analytics 4
- Google Lighthouse
- Bing Webmaster Tools

---

