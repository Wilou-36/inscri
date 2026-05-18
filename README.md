# BTS Fulbert - Application d'inscription en ligne

# 🎓 Projet Inscription BTS — Application Symfony

## 📌 Présentation

Ce projet est une application web développée avec **Symfony** permettant la gestion complète d’un dossier d’inscription en ligne pour des étudiants souhaitant intégrer un BTS.

L’application guide l’utilisateur à travers plusieurs étapes, avec validation progressive des données et dépôt de pièces justificatives.

---

## 🚀 Fonctionnalités principales

### 👤 Espace utilisateur

* Inscription / connexion sécurisée
* Création automatique d’un dossier
* Suivi de progression par étapes

### 🧾 Dossier d'inscription en 5 étapes

1. **Informations personnelles**
2. **Parcours scolaire**
3. **Choix du BTS**
4. **Upload des pièces justificatives**
5. **Validation finale du dossier**

---

### 📎 Gestion des pièces justificatives

* Upload de fichiers (PDF, JPG, PNG)

* Taille max : 5 Mo

* Types de documents :

  * Carte d’identité
  * Photo
  * Relevé de notes
  * Diplôme
  * CV
  * Certificat

* Remplacement automatique des fichiers existants

* Vérification des pièces obligatoires

---

### 🔒 Sécurité

* Accès protégé (`ROLE_USER`)
* Protection CSRF sur les formulaires
* Vérification avant soumission finale
* Blocage du dossier après validation

---

## 🛠️ Technologies utilisées

* **PHP 8+**
* **Symfony 6/7**
* **Doctrine ORM**
* **Twig**
* **MySQL**
* HTML / CSS

---

## 📂 Structure du projet

```
bts_fulbert/
├── src/
│   ├── Entity/
│   │   ├── User.php
│   │   ├── Dossier.php
│   │   └── PieceJustificative.php
│   ├── Controller/
│   │   ├── SecurityController.php
│   │   ├── InscriptionController.php
│   │   └── DashboardController.php
│   ├── Form/
│   │   ├── RegistrationFormType.php
│   │   ├── Step1PersonnelType.php
│   │   ├── Step2ScolaireType.php
│   │   ├── Step3BtsType.php
│   │   ├── Step4PiecesType.php
│   │   └── Step5RecapType.php
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   └── DossierRepository.php
│   └── Security/
│       └── AppAuthenticator.php
├── templates/
│   ├── base.html.twig
│   ├── security/
│   │   ├── login.html.twig
│   │   └── register.html.twig
│   ├── inscription/
│   │   ├── step1.html.twig
│   │   ├── step2.html.twig
│   │   ├── step3.html.twig
│   │   ├── step4.html.twig
│   │   └── step5.html.twig
│   └── dashboard/
│       └── index.html.twig
├── config/
│   ├── packages/
│   │   └── security.yaml
│   └── routes.yaml
└── migrations/
```

---

## ⚙️ Installation

### 1. Cloner le projet

```bash
git clone https://github.com/ton-repo/bts-inscription.git
cd bts-inscription
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer la base de données

Dans `.env` :

```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/bts_fulbert"
```

---

### 4. Créer la base

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

---

### 5. Lancer le serveur

```bash
symfony server:start
```

---

### 6. Cliquer sur le lien
Crtl + clic

### 7. Connection
- création de compte 
- connexion admin(
    user:
    mdp:
    )

## 📁 Upload des fichiers

Créer le dossier :

```bash
mkdir public/uploads
```

Dans `services.yaml` :

```yaml
parameters:
    uploads_directory: '%kernel.project_dir%/public/uploads'
```

---

## 🔄 Workflow utilisateur

```text
Étape 1 → Étape 2 → Étape 3 → Étape 4 → Étape 5 → Soumission
```

---

## ✅ Validation du dossier

Un dossier peut être soumis uniquement si :

* Toutes les étapes sont complètes
* Les pièces obligatoires sont présentes

---

## 📌 Améliorations possibles

* 📧 Envoi d’email de confirmation
* 🧑‍💼 Interface administrateur
* 📄 Export PDF du dossier
* 👁️ Prévisualisation des documents
* 📊 Statistiques des candidatures

---

## 👨‍💻 Auteur

Projet réalisé dans le cadre du **BTS SIO / SLAM** WILLIAMS DO REGO

---

## 📜 Licence

Projet pédagogique — usage académique

