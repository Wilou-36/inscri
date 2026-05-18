# Documentation du Flux d'Inscription BTS - Traitement Interface Étape par Étape

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Démarrage de l'interface](#démarrage-de-linterface)
3. [Flux général](#flux-général)
4. [Détail de chaque étape](#détail-de-chaque-étape)
5. [Gestion des données](#gestion-des-données)
6. [Sécurité et validations](#sécurité-et-validations)
7. [Diagrammes de flux](#diagrammes-de-flux)

---

## Vue d'Ensemble

La plateforme d'inscription est divisée en **5 étapes** progressives et sequentielles :

| Étape | Titre | Contenu |
|-------|-------|---------|
| **1** | Informations Personnelles | Identité, contact, adresse |
| **2** | Parcours Scolaire | Niveau d'études précédent |
| **3** | Choix du BTS | Sélection du programme |
| **4** | Pièces Justificatives | Upload de documents |
| **5** | Révision & Confirmation | Vérification avant soumission |

**Statuts possibles du dossier :**
- `brouillon` : En cours de remplissage (modifiable)
- `soumis` : Dossier validé et envoyé (non modifiable)
- `valide` : Accepté par l'établissement
- `refuse` : Rejeté

---

## Démarrage de l'Interface

### 1. Accès à l'Application

```
URL : /inscription/etape/1
Méthode HTTP : GET
Route : inscription_step1
```

### 2. Authentification Requise

L'utilisateur doit être connecté avec le rôle `ROLE_USER`.

```php
#[IsGranted('ROLE_USER')]
```

Si l'utilisateur n'est pas authentifié, il est redirigé vers la page de connexion.

### 3. Vérification ou Création du Dossier

La méthode `getDossierEnCours()` exécute les vérifications suivantes :

#### Étape 1 : Récupération de l'Utilisateur Actuel
```php
$user = $this->getUser();  // Utilisateur authentifié
```

#### Étape 2 : Recherche d'un Dossier Existant
```php
$dossier = $this->em->getRepository(Dossier::class)->findOneBy([
    'user' => $user,
]);
```

- Si un dossier existe → utilisation du dossier existant
- Si aucun dossier n'existe → création d'une nouvelle instance `Dossier`

#### Étape 3 : Initialisation des Propriétés

```php
// Statut par défaut
if (!$dossier->getStatut()) {
    $dossier->setStatut(Dossier::STATUT_BROUILLON);
}

// Étape actuelle par défaut
if (!$dossier->getEtapeActuelle()) {
    $dossier->setEtapeActuelle(1);
}
```

#### Étape 4 : Sauvegarde en Base de Données
```php
$this->em->flush();  // Persistance des modifications
```

**Résultat :** L'utilisateur obtient un `Dossier` prêt à être rempli.

---

## Flux Général

### Diagramme du Flux Principal

```
┌─────────────────────────────────────────┐
│ Utilisateur accède à /inscription/etape/1│
└────────────┬────────────────────────────┘
             │
             ▼
    ┌─────────────────────┐
    │ Authentifié ?       │
    └──────┬──────────┬───┘
          NON        OUI
           │          │
           ▼          ▼
        LOGIN    Dossier existe ?
                 ▼          ▼
              OUI           NON
              │             │
              └─────┬───────┘
                    ▼
        Initialiser/Récupérer Dossier
                    │
        ┌───────────┼───────────┐
        ▼           ▼           ▼
     Étape 1 → Étape 2 → Étape 3 → Étape 4 → Étape 5
                                              │
                                    ┌─────────┘
                                    ▼
                          Soumission Finale
                                    │
                          ┌─────────┘
                          ▼
                    Dashboard (Succès)
```

---

## Détail de Chaque Étape

### 🔹 ÉTAPE 1 : Informations Personnelles

#### URL & Route
```
Route : inscription_step1
URL : /inscription/etape/1
Méthode : GET/POST
```

#### Vérifications
```php
if ($dossier->getStatut() !== Dossier::STATUT_BROUILLON) {
    return $this->redirectToRoute('app_dashboard');
}
```
- Seuls les dossiers en état "brouillon" peuvent être modifiés
- Les dossiers soumis ou validés sont inaccessibles

#### Formulaire Utilisé
**Classe :** `Step1PersonnelType`

**Champs collectés :**
| Champ | Type | Validation |
|-------|------|-----------|
| `nom` | Texte | Obligatoire, max 100 caractères |
| `prenom` | Texte | Obligatoire, max 100 caractères |
| `genre` | Choix (F/M/N) | Obligatoire |
| `dateNaissance` | Date | Obligatoire, minimum 15 ans |
| `nationalite` | Texte | Obligatoire |
| `telephone` | Téléphone | Obligatoire, format valide |
| `adresse` | Texte | Obligatoire |
| `codePostal` | Texte | Obligatoire |
| `ville` | Texte | Obligatoire |

#### Traitement du Formulaire (POST)
```php
$form = $this->createForm(Step1PersonnelType::class, $dossier);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    // ✅ Validation réussie
    $dossier->setEtapeActuelle(2);  // Passage à l'étape suivante
    $this->em->flush();              // Sauvegarde en BD
    return $this->redirectToRoute('inscription_step2');
}
```

**Traitement étape par étape :**
1. Le formulaire reçoit les données POST
2. Validation des données selon les contraintes (groupe `step1`)
3. Si erreurs → affichage du formulaire avec messages d'erreur
4. Si valide → passage à l'étape 2 et redirection

#### Template
**Fichier :** `templates/inscription/step1.html.twig`

**Variables disponibles :**
```twig
form         : Objet formulaire Symfony
dossier      : Entité Dossier courante
step         : Numéro de l'étape (1)
```

---

### 🔹 ÉTAPE 2 : Parcours Scolaire

#### URL & Route
```
Route : inscription_step2
URL : /inscription/etape/2
Méthode : GET/POST
```

#### Vérifications d'Accès
```php
if ($dossier->getEtapeActuelle() < 2) {
    return $this->redirectToRoute('inscription_step1');
}
```
- L'utilisateur ne peut accéder à l'étape 2 que s'il a terminé l'étape 1
- Empêche l'accès direct par URL

#### Formulaire Utilisé
**Classe :** `Step2ScolaireType`

**Champs collectés :**
| Champ | Type | Description |
|-------|------|-------------|
| `dernier_diplome` | Choix | Baccalauréat, BTS, Licence, etc. |
| `annee_obtention` | Année | Année d'obtention |
| `etablissement_origine` | Texte | Nom du lycée/collège |
| `serie_specialite` | Texte | Série du BAC ou spécialité |
| `moyenne_generale` | Nombre | Moyenne générale obtenue |

#### Traitement Identique à l'Étape 1
```php
$form = $this->createForm(Step2ScolaireType::class, $dossier);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $dossier->setEtapeActuelle(3);
    $this->em->flush();
    return $this->redirectToRoute('inscription_step3');
}
```

---

### 🔹 ÉTAPE 3 : Choix du BTS

#### URL & Route
```
Route : inscription_step3
URL : /inscription/etape/3
Méthode : GET/POST
```

#### Vérifications d'Accès
```php
if ($dossier->getEtapeActuelle() < 3) {
    return $this->redirectToRoute('inscription_step2');
}
```

#### Formulaire Utilisé
**Classe :** `Step3BtsType`

**Programmes disponibles :**
```php
const BTS_LIST = [
    'BTS SIO SLAM'  => 'bts_sio_slam',   // Développement
    'BTS SIO SISR'  => 'bts_sio_sisr',   // Réseau
    'BTS GPME'      => 'bts_gpme',       // Gestion
    'BTS MCO'       => 'bts_mco',        // Commerce
    'BTS COMPTA'    => 'bts_compta',     // Comptabilité
    'BTS NDRC'      => 'bts_ndrc',       // Vente
];
```

**Champs collectés :**
| Champ | Type | Description |
|-------|------|-------------|
| `bts_choisi` | Choix | Sélection du programme BTS |
| `priorite_bts` | Choix (optionnel) | 2e choix de BTS |
| `motivation` | Texte long | Lettre de motivation |

#### Traitement
```php
$form = $this->createForm(Step3BtsType::class, $dossier);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $dossier->setEtapeActuelle(4);
    $this->em->flush();
    return $this->redirectToRoute('inscription_step4');
}
```

---

### 🔹 ÉTAPE 4 : Pièces Justificatives

#### URL & Route
```
Route : inscription_step4
URL : /inscription/etape/4
Méthode : GET/POST
```

#### Vérifications d'Accès
```php
if ($dossier->getEtapeActuelle() < 4) {
    return $this->redirectToRoute('inscription_step3');
}
```

#### Pièces Requises

```php
const REQUIRED_TYPES = [
    'bac_diplome',
    'bulletin_notes',
    'carte_identite',
    'justificatif_domicile',
];

const TYPES = [
    'bac_diplome'         => 'Diplôme du BAC',
    'bulletin_notes'      => 'Bulletins de notes',
    'carte_identite'      => 'Pièce d\'identité',
    'justificatif_domicile' => 'Justificatif de domicile',
    'certificat_scolarite' => 'Certificat de scolarité (optionnel)',
    'lettre_motivation'   => 'Lettre de motivation (optionnel)',
];
```

#### Formulaire Utilisé
**Classe :** `Step4PiecesType`

Pour chaque type de pièce, un champ upload est créé.

#### Traitement des Uploads

**Étape 1 : Récupération des pièces existantes**
```php
$piecesExistantes = [];
foreach ($dossier->getPiecesJustificatives() as $piece) {
    $piecesExistantes[$piece->getType()] = $piece;
}
```

**Étape 2 : Traitement pour chaque fichier**
```php
foreach (PieceJustificative::TYPES as $key => $label) {
    $file = $form->get($key)->getData();
    
    if ($file) {
        // 1️⃣ Récupération des métadonnées AVANT le move
        $mimeType = $file->getMimeType();  // Ex: "application/pdf"
        $size = $file->getSize();           // Ex: 2048576 (bytes)
        
        // 2️⃣ Suppression de l'ancienne pièce si elle existe
        if (isset($piecesExistantes[$key])) {
            $this->em->remove($piecesExistantes[$key]);
        }
        
        // 3️⃣ Génération d'un nom unique
        $filename = uniqid() . '.' . $file->guessExtension();
        // Ex: "64c8d9e7f1a2b.pdf"
        
        // 4️⃣ Déplacement du fichier
        $file->move(
            $this->getParameter('upload_dir'),  // var/uploads/
            $filename
        );
        
        // 5️⃣ Création de l'entité PieceJustificative
        $piece = new PieceJustificative();
        $piece->setType($key);
        $piece->setNomFichier($filename);
        $piece->setCheminFichier('uploads/' . $filename);
        $piece->setMimeType($mimeType);
        $piece->setTaille($size);
        $piece->setDossier($dossier);
        
        // 6️⃣ Sauvegarde en BD
        $this->em->persist($piece);
    }
}

// 7️⃣ Validation complète et passage à l'étape 5
$dossier->setEtapeActuelle(5);
$this->em->flush();
return $this->redirectToRoute('inscription_step5');
```

#### Validation des Fichiers
- **Formats acceptés :** PDF, JPG, PNG, DOCX, ZIP
- **Taille maximale :** 10 MB par fichier
- **Pièces obligatoires :** 4 (vérifiées à l'étape 5)

---

### 🔹 ÉTAPE 5 : Révision & Confirmation

#### URL & Route
```
Route : inscription_step5
URL : /inscription/etape/5
Méthode : GET (affichage), POST (soumission)
```

#### Vérifications d'Accès
```php
if ($dossier->getEtapeActuelle() < 5) {
    return $this->redirectToRoute('inscription_step4');
}
```

#### Vérification des Pièces Manquantes

```php
$typesPresents = [];

// 1️⃣ Collecte des types de pièces uploadées
foreach ($dossier->getPiecesJustificatives() as $piece) {
    $typesPresents[] = $piece->getType();
}

// 2️⃣ Récupération des types requis
$typesRequis = PieceJustificative::REQUIRED_TYPES;

// 3️⃣ Calcul des pièces manquantes
$piecesManquantes = array_diff($typesRequis, $typesPresents);
```

**Exemple :**
```
Pièces requises: [bac_diplome, bulletin_notes, carte_identite, justificatif_domicile]
Pièces présentes: [bac_diplome, carte_identite, justificatif_domicile]
Pièces manquantes: [bulletin_notes]
```

#### Affichage du Template
```php
return $this->render('inscription/step5.html.twig', [
    'dossier' => $dossier,
    'piecesManquantes' => $piecesManquantes,
    'step' => 5,
]);
```

#### Variables du Template
```twig
dossier           : Entité Dossier complète
piecesManquantes  : Array des types de pièces manquantes
step              : Numéro de l'étape (5)
```

#### Contenu de l'Écran d'Étape 5
- ✅ Affichage de toutes les informations saisies
- ✅ Liste des pièces uploadées
- ⚠️ Alertes si pièces manquantes
- ✅ Bouton "Confirmer et Soumettre" (actif si aucune pièce manquante)

---

### 🔹 SOUMISSION FINALE

#### URL & Route
```
Route : inscription_soumettre
URL : /inscription/soumettre
Méthode : POST (formulaire)
```

#### Vérifications de Sécurité

**1. Token CSRF**
```php
if (!$this->isCsrfTokenValid('soumettre_dossier', $request->request->get('_token'))) {
    return $this->redirectToRoute('inscription_step5');
}
```

Protège contre les attaques CSRF (Cross-Site Request Forgery).

**2. Dossier Peut Être Soumis**
```php
if (!$dossier->peutEtreSoumis()) {
    return $this->redirectToRoute('inscription_step5');
}
```

Vérifie que :
- Toutes les étapes sont complétées
- Toutes les pièces obligatoires sont présentes
- Le dossier est en statut "brouillon"

#### Traitement de la Soumission
```php
// 1️⃣ Modification du statut
$dossier->setStatut(Dossier::STATUT_SOUMIS);

// 2️⃣ Enregistrement de la date/heure de soumission
$dossier->setSoumisAt(new \DateTimeImmutable());

// 3️⃣ Sauvegarde en BD
$this->em->flush();

// 4️⃣ Redirection vers le dashboard
return $this->redirectToRoute('app_dashboard');
```

#### États Après Soumission
- **Dossier** : Statut changé à `soumis`
- **Édition** : Dossier verrouillé (non modifiable)
- **Page** : Redirection vers dashboard
- **Notification** : Message de confirmation (optionnel)

---

## Gestion des Données

### Persistence des Données

#### Concept : Sauvegarde Progressive
Chaque étape sauvegarde automatiquement les données en base de données.

#### Workflows de Sauvegarde

**Étape 1-4 : Progression**
```
POST → Validation ✅ → setEtapeActuelle(+1) → flush() → Redirect étape suivante
```

**Étape 5 : Confirmation**
```
POST → Validation CSRF ✅ → Vérif pièces ✅ → setStatut(SOUMIS) → flush() → Dashboard
```

### Entités Impliquées

#### Entity: Dossier
```
Dossier
├── id                    (int, PK)
├── user_id              (FK → User)
├── etapeActuelle        (int, 1-5)
├── statut               (string: brouillon|soumis|valide|refuse)
├── nom, prenom, genre   (Étape 1)
├── nationalite, etc.    (Étape 1)
├── dernier_diplome      (Étape 2)
├── bts_choisi           (Étape 3)
├── piecesJustificatives (Collection)
├── createdAt            (DateTime)
├── soumisAt             (DateTime nullable)
└── updatedAt            (DateTime)
```

#### Entity: PieceJustificative
```
PieceJustificative
├── id                 (int, PK)
├── dossier_id         (FK → Dossier)
├── type               (string: bac_diplome|bulletin_notes|...)
├── nomFichier         (string)
├── cheminFichier      (string: uploads/64c8d9e7f1a2b.pdf)
├── mimeType           (string: application/pdf)
├── taille             (int)
└── uploadedAt         (DateTime)
```

---

## Sécurité et Validations

### 1. Authentification & Autorisation

#### Protection au Niveau du Contrôleur
```php
#[IsGranted('ROLE_USER')]
class InscriptionController extends AbstractController
```

- ✅ Seuls les utilisateurs connectés accèdent à `/inscription`
- ❌ Utilisateurs anonymes → redirection login

#### Contrôle d'Accès par Étape
```php
if ($dossier->getEtapeActuelle() < 2) {
    return $this->redirectToRoute('inscription_step1');
}
```

- ✅ Impossible d'accéder à l'étape 2 sans terminer l'étape 1
- ✅ Impossible de contourner par URL directe

### 2. Validation des Données

#### Validations au Niveau du Formulaire

**Groupe de validation par étape :**
```php
#[Assert\NotBlank(message: 'Nom obligatoire', groups: ['step1'])]
private ?string $nom = null;
```

#### Validations au Niveau de l'Entité

**Exemples :**
```php
#[Assert\LessThan(value: 'today', message: 'Date invalide', groups: ['step1'])]
private ?\DateTimeInterface $dateNaissance = null;

#[Assert\Regex(
    pattern: '/^[0-9\s\+\-]{10,15}$/',
    message: 'Téléphone invalide',
    groups: ['step1']
)]
private ?string $telephone = null;
```

#### Validations au Niveau du Contrôleur

```php
if ($form->isSubmitted() && $form->isValid()) {
    // Les données sont garanties valides
    // Traitement sûr
}
```

### 3. Protection CSRF

#### Token CSRF à la Soumission
```html
<!-- Dans le formulaire -->
{{ csrf_token('soumettre_dossier') }}
```

```php
// Vérification du token
if (!$this->isCsrfTokenValid('soumettre_dossier', $request->request->get('_token'))) {
    return $this->redirectToRoute('inscription_step5');
}
```

Prévient les attaques CSRF.

### 4. Protection des Upload de Fichiers

#### Vérifications Recommandées

**À implémenter :**
```php
// ✅ Vérifier le MIME type
if (!in_array($file->getMimeType(), ['application/pdf', 'image/jpeg', 'image/png'])) {
    throw new \Exception('Format non accepté');
}

// ✅ Vérifier la taille
if ($file->getSize() > 10 * 1024 * 1024) { // 10 MB
    throw new \Exception('Fichier trop volumineux');
}

// ✅ Vérifier le nom de fichier
$filename = uniqid() . '.' . $file->guessExtension();
// Évite les injections de chemin

// ✅ Stocker en dehors du web root
$uploadDir = $this->getParameter('upload_dir');
// Exemple: /var/uploads/ (pas accessible directement)
```

### 5. Intégrité des Données

#### Vérification avant Soumission
```php
if (!$dossier->peutEtreSoumis()) {
    return $this->redirectToRoute('inscription_step5');
}
```

Méthode qui vérifie :
- ✅ Toutes les étapes complétées
- ✅ Toutes les pièces obligatoires présentes
- ✅ Aucune données manquantes

---

## Diagrammes de Flux

### Diagramme Complet de l'Inscription

```
START
  │
  ▼
┌─────────────────────┐
│ Accès /inscription  │
└──────────┬──────────┘
           │
  ┌────────▼────────┐
  │ Authentifié ?   │
  └────┬──────┬─────┘
      NON    OUI
       │      │
       ▼      ▼
    [LOGIN] [Étape 1]
             └──┬──────────────────┐
                │                  │
             ┌──▼──┐           ┌───▼───┐
             │ GET │           │ POST  │
             └──┬──┘           └───┬───┘
                │                  │
         [Afficher]          ┌──────▼──────┐
         Formulaire   │Valide│
                          └──┬──┬─────┘
                          OUI  NON
                           │    │
                           │    └─→ [Afficher erreurs]
                           │
                        ┌──▼──────────┐
                        │ saveEtape 1 │
                        │ setEtape(2) │
                        └──┬──────────┘
                           │
                        ┌──▼──────┐
                        │ Étape 2 │───────┐
                        └──┬──────┘       │
                           │             │
                    ┌──────▼─────┐   [Étapes 3-4
                    │ Étape 2    │    similaires]
                    │ [GET/POST] │       │
                    └──┬─────────┘       │
                       │◄────────────────┘
                    ┌──▼──────┐
                    │ Étape 5 │
                    └──┬──────┘
                       │
        ┌──────────────▼──────────────┐
        │ Vérifier pièces manquantes  │
        └──────┬──────────────┬───────┘
               │              │
            OUI│              │NON
               │              │
        ┌──────▼──────┐  ┌────▼──────────┐
        │[Afficher]   │  │[Alert]Pièces  │
        │Formulaire   │  │manquantes     │
        │soumission   │  │[Retour Upload]│
        └──┬──────────┘  └───────────────┘
           │
       [POST]
           │
    ┌──────▼──────────┐
    │Vérif Token CSRF │
    └────┬─────┬──────┘
      VALIDE INVALIDE
         │       │
         │       └──→ [Redirection Étape 5]
         │
    ┌────▼──────────────────┐
    │setStatut(SOUMIS)      │
    │setSoumisAt(NOW)       │
    │flush()                │
    └────┬──────────────────┘
         │
    ┌────▼──────────────┐
    │Redirection        │
    │Dashboard          │
    └────┬──────────────┘
         │
        END
```

### Diagramme États du Dossier

```
┌──────────────┐
│  BROUILLON   │ ◄──────────────────┐
├──────────────┤                    │
│ - Modifiable │                    │
│ - En cours   │                    │
└───────┬──────┘                    │
        │                      ┌────┴────────┐
        │                      │ Retour Étape│
        │                      │   d'avant   │
        │                      └─────────────┘
        │
        │ [Soumission finale OK]
        │
        ▼
┌──────────────┐
│   SOUMIS     │
├──────────────┤
│- Non modifié │
│- En attente  │
│  d'examen    │
└──────────────┘
        │
    ┌───┴────┬──────────┐
    │        │          │
    ▼        ▼          ▼
┌───────┐┌──────┐  ┌────────┐
│VALIDE ││REFUSE│  │En cours│
└───────┘└──────┘  └────────┘
```

### Diagramme Flux Formulaire Étape 1

```
GET /inscription/etape/1
│
▼
┌──────────────────────┐
│ Récupérer dossier    │
│ (ou créer nouveau)   │
└──────┬───────────────┘
       │
       ▼
┌────────────────────────┐
│ createForm(            │
│   Step1PersonnelType,  │
│   $dossier             │
│ )                      │
└──────┬─────────────────┘
       │
       ▼
┌────────────────────────┐
│ Render step1.twig      │
│ avec form->createView()│
└────────────────────────┘
       │
       │ [Utilisateur saisit]
       │ [Utilisateur clique]
       │
       ▼
POST /inscription/etape/1
│
▼
┌──────────────────────┐
│ form->handleRequest()│
└──────┬───────────────┘
       │
       ▼
┌──────────────────────────┐
│ isSubmitted() &&         │
│ isValid() ?              │
└────┬──────────────┬──────┘
   OUI              NON
    │                │
    ▼                ▼
Valide          Erreurs
    │                │
    └────┬───────────┘
         │
    ┌────▼──────────────┐
    │ setEtapeActuelle  │
    │ (2)               │
    │ flush()           │
    └────┬──────────────┘
         │
         ▼
    ┌─────────────────┐
    │ Redirect        │
    │ step2           │
    └─────────────────┘
```

---

## Résumé des Flux Clés

### Flux 1 : Accès Initial
```
GET /inscription/etape/1
  └─ Vérif : Utilisateur connecté ?
  └─ Récupère/Crée Dossier
  └─ Initialise Étape = 1, Statut = BROUILLON
  └─ Affiche formulaire vierge
```

### Flux 2 : Progression Entre Étapes
```
POST /inscription/etape/N
  └─ Vérif : Données valides ?
  └─ YES → Incrémente étapeActuelle
  └─ YES → flush() en BD
  └─ YES → Redirect vers étape N+1
  └─ NO  → Affiche erreurs et reforme
```

### Flux 3 : Vérification d'Accès
```
GET /inscription/etape/N
  └─ Vérif : dossier.etapeActuelle >= N ?
  └─ NO  → Redirect vers dernière étape accessible
  └─ YES → Affiche écran
```

### Flux 4 : Soumission Finale
```
POST /inscription/soumettre
  └─ Vérif CSRF Token
  └─ Vérif Pièces manquantes ?
  └─ YES → Affiche erreurs
  └─ NO  → Statut = SOUMIS
  └─ NO  → soumisAt = NOW
  └─ NO  → Redirect Dashboard
```

---

## Checklist d'Implémentation

### Phase 1 : Configuration
- [ ] `Step1PersonnelType` complet et validé
- [ ] `Step2ScolaireType` complet et validé
- [ ] `Step3BtsType` complet et validé
- [ ] `Step4PiecesType` complet et validé
- [ ] Entité `Dossier` complète
- [ ] Entité `PieceJustificative` complète

### Phase 2 : Contrôleur
- [ ] Méthode `getDossierEnCours()` implémentée
- [ ] Méthode `step1()` implémentée
- [ ] Méthode `step2()` implémentée
- [ ] Méthode `step3()` implémentée
- [ ] Méthode `step4()` implémentée
- [ ] Méthode `step5()` implémentée
- [ ] Méthode `soumettre()` implémentée

### Phase 3 : Templates
- [ ] `step1.html.twig` créé
- [ ] `step2.html.twig` créé
- [ ] `step3.html.twig` créé
- [ ] `step4.html.twig` créé
- [ ] `step5.html.twig` créé
- [ ] `_stepper.html.twig` créé
- [ ] Protection CSRF dans tous les formulaires

### Phase 4 : Sécurité
- [ ] Authentification requise (`#[IsGranted]`)
- [ ] Vérifications d'accès aux étapes
- [ ] Validations côté serveur
- [ ] Protection CSRF
- [ ] Upload fichiers sécurisé
- [ ] Vérification des MIME types
- [ ] Limitation taille fichiers

### Phase 5 : Tests
- [ ] Test flux complet inscription
- [ ] Test accès aux étapes
- [ ] Test validation formulaires
- [ ] Test upload fichiers
- [ ] Test soumission finale
- [ ] Test sécurité CSRF
- [ ] Test redirection non-authentifiés

---

## Conclusion

Le système d'inscription BTS fonctionne selon un modèle **progressif et séquentiel** :

1. **Chaque étape est protégée** : accès uniquement si étapes précédentes complétées
2. **Chaque étape valide ses données** : avant progression
3. **Données persistent progressivement** : sauvegarde après chaque étape
4. **Soumission finale et définitive** : changement de statut et verrouillage
5. **Sécurité multi-couches** : authentification, validation, CSRF, uploads

Ce design garantit une expérience utilisateur fluide tout en maintenant l'intégrité des données et la sécurité.
