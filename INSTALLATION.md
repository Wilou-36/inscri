# Guide d'installation — BTS Fulbert

## 1. Créer le projet Symfony

```bash
composer create-project symfony/skeleton:"7.2.*" bts_fulbert
cd bts_fulbert

# Bundles nécessaires
composer require symfony/orm-pack                  # Doctrine ORM
composer require symfony/form                      # FormBuilder
composer require symfony/validator                 # Validation
composer require symfony/security-bundle          # Auth + CSRF
composer require symfony/twig-bundle             # Twig
composer require symfony/asset                    # Assets
composer require symfony/mailer                   # Email (optionnel)
composer require symfony/string                   # SluggerInterface
composer require symfony/password-hasher         # Password hashing
composer require --dev symfony/maker-bundle      # Générateurs
composer require --dev doctrine/doctrine-fixtures-bundle
```

## 2. Configuration base de données (.env)

```env
# MySQL
DATABASE_URL="mysql://root:password@127.0.0.1:3306/bts_fulbert?serverVersion=8.0"

# PostgreSQL (alternative)
DATABASE_URL="postgresql://postgres:password@127.0.0.1:5432/bts_fulbert?serverVersion=15"
```

## 3. Créer la base et les migrations

```bash
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## 4. Configurer le répertoire d'upload (services.yaml)

```yaml
# config/services.yaml
parameters:
    upload_dir: '%kernel.project_dir%/public/uploads/dossiers'

services:
    App\Controller\InscriptionController:
        arguments:
            $uploadDir: '%upload_dir%'
```

## 5. Configurer le kernel.secret (.env)

```env
APP_SECRET=votre_secret_aleatoire_32_chars
APP_ENV=prod
```

## 6. Lancer le serveur de développement

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

---

## Architecture multi-step expliquée

### Principe de sauvegarde progressive

Le cœur du système repose sur l'entité `Dossier` :

```
User ──< Dossier >── PieceJustificative
            │
            ├── etapeActuelle (1-5)   ← pointeur de progression
            ├── statut (brouillon/soumis/validé)
            └── champs par étape (nullable)
```

À chaque soumission d'étape :
1. Le formulaire valide uniquement le **groupe de validation** de l'étape (`step1`, `step2`…)
2. Les données sont persistées en base immédiatement (`$em->flush()`)
3. `etapeActuelle` est incrémenté si nécessaire
4. L'utilisateur est redirigé vers l'étape suivante

### Reprise du dossier

```php
// InscriptionController::getDossierEnCours()
$dossier = $em->getRepository(Dossier::class)->findOneBy([
    'user'   => $user,
    'statut' => Dossier::STATUT_BROUILLON,
]);
// S'il n'existe pas → création automatique
```

L'utilisateur peut fermer son navigateur et reprendre plus tard : le dossier est en `statut=brouillon` avec `etapeActuelle=3` par exemple.

### Groupes de validation Symfony

```php
// Dans Dossier.php
#[Assert\NotBlank(groups: ['step1'])]
private ?string $nom = null;

// Dans le contrôleur
$form = $this->createForm(Step1PersonnelType::class, $dossier, [
    'validation_groups' => ['Default', 'step1'],
]);
```

Cela permet de valider **uniquement les champs de l'étape en cours**, même si les champs des autres étapes sont encore `null`.

### Protection CSRF

Symfony active automatiquement la protection CSRF sur tous les formulaires via `form_login > enable_csrf: true` et le `CsrfTokenManager`. Pour la soumission finale (formulaire HTML natif), on utilise :

```twig
{# Génération #}
<input type="hidden" name="_token" value="{{ csrf_token('soumettre_dossier') }}">

{# Vérification dans le contrôleur #}
$this->isCsrfTokenValid('soumettre_dossier', $request->request->get('_token'))
```

### Upload sécurisé

- Validation du MIME type côté serveur (pas seulement l'extension)
- Nom de fichier slugifié + `uniqid()` pour éviter les collisions et traversals
- Taille limitée à 5 Mo
- Stockage hors `public/` recommandé en production

---

## Structure des fichiers à copier

```
src/Entity/User.php              → entité User + UserInterface
src/Entity/Dossier.php           → entité principale (multi-step)
src/Entity/PieceJustificative.php → fichiers uploadés
src/Controller/SecurityController.php    → login / register / verify
src/Controller/InscriptionController.php → 5 étapes + soumission
src/Controller/DashboardController.php   → tableau de bord
src/Form/Forms.php               → tous les FormType (à séparer en fichiers)
config/packages/security.yaml
templates/base.html.twig
templates/security/login.html.twig
templates/inscription/_stepper.html.twig
templates/inscription/step1-5.html.twig
templates/dashboard/index.html.twig
```
