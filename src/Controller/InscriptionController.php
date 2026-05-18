<?php

/**
 * InscriptionController
 * 
 * Gère le flux d'inscription multi-étapes pour les candidatures BTS.
 * 
 * Flux:
 * - Étape 1: Informations personnelles (nom, prénom, adresse...)
 * - Étape 2: Parcours scolaire (diplôme, mention, établissement...)
 * - Étape 3: Choix du BTS et lettre de motivation
 * - Étape 4: Upload des pièces justificatives
 * - Étape 5: Récapitulatif et validation avant soumission
 * - Soumission finale: Passage du dossier en statut "soumis"
 * 
 * @author Lycée Fulbert
 * @package App\Controller
 */

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\PieceJustificative;
use App\Form\Step1PersonnelType;
use App\Form\Step2ScolaireType;
use App\Form\Step3BtsType;
use App\Form\Step4PiecesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/inscription')]
#[IsGranted('ROLE_USER')] // Seuls les utilisateurs authentifiés peuvent accéder
class InscriptionController extends AbstractController
{
    /**
     * Constructeur avec injection de dépendances
     * 
     * @param EntityManagerInterface $em - Gestionnaire Doctrine (accès BDD)
     * @param Security $security - Service de sécurité Symfony
     * @param SluggerInterface $slugger - Service de génération de slugs
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly SluggerInterface $slugger
    ) {}

    /**
     * Récupère ou crée le dossier actuel de l'utilisateur
     * 
     * Cette méthode garantit qu'un dossier existe toujours pour l'utilisateur courant.
     * Si aucun dossier n'existe, un nouveau est créé avec le statut "brouillon".
     * 
     * @return Dossier Le dossier de l'utilisateur
     */
    private function getDossierEnCours(): Dossier
    {
        $user = $this->getUser();

        // Cherche un dossier existant pour l'utilisateur
        $dossier = $this->em->getRepository(Dossier::class)->findOneBy([
            'user' => $user,
        ]);

        // Si aucun dossier n'existe, en créer un nouveau
        if (!$dossier) {
            $dossier = new Dossier();
            $dossier->setUser($user);
            $dossier->setStatut(Dossier::STATUT_BROUILLON);
            $dossier->setEtapeActuelle(1);
            // IMPORTANT: persist() est obligatoire pour un nouvel objet
            $this->em->persist($dossier);
            $this->em->flush();
        }

        return $dossier;
    }

    /**
     * ÉTAPE 1 - Informations personnelles
     * 
     * Affiche et traite le formulaire d'informations personnelles:
     * - Identité (nom, prénom, genre, date de naissance)
     * - Nationalité et téléphone
     * - Adresse et localité
     * 
     * Après validation, met à jour l'étape à 2 et redirige vers step2.
     * 
     * @param Request $request Requête HTTP
     * @return Response Réponse (formulaire ou redirection)
     */
    #[Route('/etape/1', name: 'inscription_step1')]
    public function step1(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        // Vérification: seuls les dossiers en brouillon peuvent être modifiés
        if ($dossier->getStatut() !== Dossier::STATUT_BROUILLON) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Création du formulaire avec validation du groupe "step1"
        $form = $this->createForm(Step1PersonnelType::class, $dossier);
        $form->handleRequest($request);

        // Si formulaire soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Conditionnel: ne jamais rétrograder la progression
            if ($dossier->getEtapeActuelle() < 2) {
                $dossier->setEtapeActuelle(2);
            }
            // Enregistrement en BDD
            $this->em->flush();

            // Message de confirmation pour l'utilisateur
            $this->addFlash('success', 'Informations personnelles enregistrées.');
            
            // Redirection vers l'étape suivante
            return $this->redirectToRoute('inscription_step2');
        }

        // Affiche le formulaire (vide ou avec erreurs)
        return $this->render('inscription/step1.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 1, // Pour afficher le stepper
        ]);
    }

    /**
     * ÉTAPE 2 - Parcours scolaire
     * 
     * Affiche et traite le formulaire du parcours scolaire:
     * - Dernier diplôme obtenu
     * - Mention et année d'obtention
     * - Établissement d'origine
     * - Expérience professionnelle (optionnel)
     * 
     * @param Request $request Requête HTTP
     * @return Response Réponse (formulaire ou redirection)
     */
    #[Route('/etape/2', name: 'inscription_step2')]
    public function step2(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        // Vérification: impossible d'accéder si étape précédente non complétée
        if ($dossier->getEtapeActuelle() < 2) {
            return $this->redirectToRoute('inscription_step1');
        }

        $form = $this->createForm(Step2ScolaireType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dossier->getEtapeActuelle() < 3) {
                $dossier->setEtapeActuelle(3);
            }
            $this->em->flush();
            $this->addFlash('success', '✅ Parcours scolaire enregistré.');
            return $this->redirectToRoute('inscription_step3');
        }

        return $this->render('inscription/step2.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 2,
        ]);
    }

    /**
     * ÉTAPE 3 - Choix du BTS et lettre de motivation
     * 
     * Permet au candidat de:
     * - Sélectionner son BTS principal parmi les 6 options
     * - Écrire sa lettre de motivation (min 200 caractères)
     * - Sélectionner optionnellement un 2e BTS
     * 
     * @param Request $request Requête HTTP
     * @return Response Réponse
     */
    #[Route('/etape/3', name: 'inscription_step3')]
    public function step3(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getEtapeActuelle() < 3) {
            return $this->redirectToRoute('inscription_step2');
        }

        $form = $this->createForm(Step3BtsType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dossier->getEtapeActuelle() < 4) {
                $dossier->setEtapeActuelle(4);
            }
            $this->em->flush();
            $this->addFlash('success', '✅ Choix du BTS enregistré.');
            return $this->redirectToRoute('inscription_step4');
        }

        return $this->render('inscription/step3.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 3,
        ]);
    }

    /**
     * ÉTAPE 4 - Upload des pièces justificatives
     * 
     * Gère l'upload et la gestion des fichiers:
     * - Récupère les fichiers du formulaire
     * - Déplace les fichiers dans le dossier uploads/
     * - Crée les entrées PieceJustificative en BDD
     * - Supprime les anciens fichiers en cas de remplacement
     * 
     * @param Request $request Requête HTTP
     * @return Response Réponse
     */
    #[Route('/etape/4', name: 'inscription_step4')]
    public function step4(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getEtapeActuelle() < 4) {
            return $this->redirectToRoute('inscription_step3');
        }

        $form = $this->createForm(Step4PiecesType::class);
        $form->handleRequest($request);

        // Index les pièces existantes par type pour gestion des remplacements
        $piecesExistantes = [];
        foreach ($dossier->getPiecesJustificatives() as $piece) {
            $piecesExistantes[$piece->getType()] = $piece;
        }

        if ($form->isSubmitted() && $form->isValid()) {

            // Parcourt chaque type de pièce justificative
            foreach (PieceJustificative::TYPES as $key => $label) {

                // Récupère le fichier uploadé pour ce type
                $file = $form->get($key)->getData();

                if ($file) {

                    // IMPORTANT: récupérer les métadonnées AVANT le move
                    $mimeType = $file->getMimeType();
                    $size = $file->getSize();

                    // Supprimer l'ancienne pièce si elle existe
                    if (isset($piecesExistantes[$key])) {
                        $this->em->remove($piecesExistantes[$key]);
                    }

                    // Génère un nom de fichier unique
                    $filename = uniqid().'.'.$file->guessExtension();

                    // Déplace le fichier vers le dossier uploads/
                    $file->move(
                        $this->getParameter('upload_dir'),
                        $filename
                    );

                    // Crée l'enregistrement en BDD
                    $piece = new PieceJustificative();
                    $piece->setType($key);
                    $piece->setNomFichier($filename);
                    $piece->setCheminFichier('uploads/'.$filename);
                    $piece->setMimeType($mimeType);
                    $piece->setTaille($size);
                    $piece->setDossier($dossier);

                    $this->em->persist($piece);
                }
            }

            // Passage à l'étape suivante (5)
            if ($dossier->getEtapeActuelle() < 5) {
                $dossier->setEtapeActuelle(5);
            }
            $this->em->flush();

            $this->addFlash('success', '✅ Pièces justificatives enregistrées.');
            return $this->redirectToRoute('inscription_step5');
        }

        return $this->render('inscription/step4.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 4,
            'types' => PieceJustificative::TYPES,
            'typesRequis' => PieceJustificative::REQUIRED_TYPES,
            'piecesExistantes' => $piecesExistantes,
        ]);
    }

    /**
     * ÉTAPE 5 - Récapitulatif et validation
     * 
     * Affiche un résumé complet du dossier:
     * - Infos personnelles
     * - Parcours scolaire
     * - Choix du BTS
     * - Pièces justificatives
     * 
     * Vérifie qu'aucune pièce obligatoire ne manque.
     * 
     * @return Response Réponse
     */
    #[Route('/etape/5', name: 'inscription_step5')]
    public function step5(): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getEtapeActuelle() < 5) {
            return $this->redirectToRoute('inscription_step4');
        }

        // Récupère la liste des types de pièces présentes
        $typesPresents = [];
        foreach ($dossier->getPiecesJustificatives() as $piece) {
            $typesPresents[] = $piece->getType();
        }

        // Identifie les pièces manquantes
        $typesRequis = PieceJustificative::REQUIRED_TYPES;
        $piecesManquantes = array_diff($typesRequis, $typesPresents);

        return $this->render('inscription/step5.html.twig', [
            'dossier' => $dossier,
            'piecesManquantes' => $piecesManquantes,
            'step' => 5,
        ]);
    }

    /**
     * Soumission finale du dossier
     * 
     * Action POST sécurisée qui:
     * - Vérifie le token CSRF
     * - Change le statut du dossier en "soumis"
     * - Enregistre la date/heure de soumission
     * - Redirige vers le dashboard
     * 
     * ⚠️ Cette action est IRRÉVERSIBLE
     * 
     * @param Request $request Requête HTTP
     * @return Response Réponse (redirection)
     */
    #[Route('/soumettre', name: 'inscription_soumettre', methods: ['POST'])]
    public function soumettre(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        // Vérification du token CSRF (sécurité anti-CSRF)
        if (!$this->isCsrfTokenValid('soumettre_dossier', $request->request->get('_token'))) {
            return $this->redirectToRoute('inscription_step5');
        }

        // Vérification finale: le dossier peut-il être soumis?
        if (!$dossier->peutEtreSoumis()) {
            return $this->redirectToRoute('inscription_step5');
        }

        // Passage au statut "soumis" (irrévocable)
        $dossier->setStatut(Dossier::STATUT_SOUMIS);
        $dossier->setSoumisAt(new \DateTimeImmutable());

        // Enregistrement
        $this->em->flush();

        // Redirection vers le dashboard
        return $this->redirectToRoute('app_dashboard');
    }
}

    // ─────────────────────────────
    //  RÉCUPÉRER DOSSIER
    // ─────────────────────────────

    private function getDossierEnCours(): Dossier
    {
        $user = $this->getUser();

        $dossier = $this->em->getRepository(Dossier::class)->findOneBy([
            'user' => $user,
        ]);

        if (!$dossier) {
            $dossier = new Dossier();
            $dossier->setUser($user);
            $dossier->setStatut(Dossier::STATUT_BROUILLON);
            $dossier->setEtapeActuelle(1);
            $this->em->persist($dossier); // ✅ persist() OBLIGATOIRE pour un nouveau dossier
            $this->em->flush();
        }

        return $dossier;
    }

    // ─────────────────────────────
    // ÉTAPE 1
    // ─────────────────────────────

    #[Route('/etape/1', name: 'inscription_step1')]
    public function step1(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getStatut() !== Dossier::STATUT_BROUILLON) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(Step1PersonnelType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Conditionnel : ne jamais rétrograder la progression
            if ($dossier->getEtapeActuelle() < 2) {
                $dossier->setEtapeActuelle(2);
            }
            $this->em->flush();

            $this->addFlash('success', '✅ Informations personnelles enregistrées.');
            return $this->redirectToRoute('inscription_step2');
        }

        return $this->render('inscription/step1.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 1,
        ]);
    }

    // ─────────────────────────────
    // ÉTAPE 2
    // ─────────────────────────────

    #[Route('/etape/2', name: 'inscription_step2')]
    public function step2(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getEtapeActuelle() < 2) {
            return $this->redirectToRoute('inscription_step1');
        }

        $form = $this->createForm(Step2ScolaireType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Conditionnel
            if ($dossier->getEtapeActuelle() < 3) {
                $dossier->setEtapeActuelle(3);
            }
            $this->em->flush();

            $this->addFlash('success', '✅ Parcours scolaire enregistré.');
            return $this->redirectToRoute('inscription_step3');
        }

        return $this->render('inscription/step2.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 2,
        ]);
    }

    // ─────────────────────────────
    // ÉTAPE 3
    // ─────────────────────────────

    #[Route('/etape/3', name: 'inscription_step3')]
    public function step3(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getEtapeActuelle() < 3) {
            return $this->redirectToRoute('inscription_step2');
        }

        $form = $this->createForm(Step3BtsType::class, $dossier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Conditionnel
            if ($dossier->getEtapeActuelle() < 4) {
                $dossier->setEtapeActuelle(4);
            }
            $this->em->flush();

            $this->addFlash('success', '✅ Choix du BTS enregistré.');
            return $this->redirectToRoute('inscription_step4');
        }

        return $this->render('inscription/step3.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 3,
        ]);
    }

    // ─────────────────────────────
    // ÉTAPE 4 (UPLOAD)
    // ─────────────────────────────

    #[Route('/etape/4', name: 'inscription_step4')]
    public function step4(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getEtapeActuelle() < 4) {
            return $this->redirectToRoute('inscription_step3');
        }

        $form = $this->createForm(Step4PiecesType::class);
        $form->handleRequest($request);

        //  pièces existantes
        $piecesExistantes = [];
        foreach ($dossier->getPiecesJustificatives() as $piece) {
            $piecesExistantes[$piece->getType()] = $piece;
        }

        if ($form->isSubmitted() && $form->isValid()) {

            foreach (PieceJustificative::TYPES as $key => $label) {

                $file = $form->get($key)->getData();

                if ($file) {

                    // ✅ récupérer AVANT le move
                    $mimeType = $file->getMimeType();
                    $size = $file->getSize();

                    // suppression ancien fichier
                    if (isset($piecesExistantes[$key])) {
                        $this->em->remove($piecesExistantes[$key]);
                    }

                    $filename = uniqid().'.'.$file->guessExtension();

                    $file->move(
                        $this->getParameter('upload_dir'),
                        $filename
                    );

                    $piece = new PieceJustificative();
                    $piece->setType($key);
                    $piece->setNomFichier($filename);
                    $piece->setCheminFichier('uploads/'.$filename);
                    $piece->setMimeType($mimeType); // ✅ OK
                    $piece->setTaille($size);       // ✅ OK
                    $piece->setDossier($dossier);

                    $this->em->persist($piece);
                }
            }

            // ✅ Conditionnel
            if ($dossier->getEtapeActuelle() < 5) {
                $dossier->setEtapeActuelle(5);
            }
            $this->em->flush();

            $this->addFlash('success', '✅ Pièces justificatives enregistrées.');
            return $this->redirectToRoute('inscription_step5');
        }

        return $this->render('inscription/step4.html.twig', [
            'form' => $form->createView(),
            'dossier' => $dossier,
            'step' => 4,
            'types' => PieceJustificative::TYPES,
            'typesRequis' => PieceJustificative::REQUIRED_TYPES,
            'piecesExistantes' => $piecesExistantes,
        ]);
    }

    // ─────────────────────────────
    // ÉTAPE 5 (VALIDATION)
    // ─────────────────────────────

    #[Route('/etape/5', name: 'inscription_step5')]
    public function step5(): Response
    {
        $dossier = $this->getDossierEnCours();

        if ($dossier->getEtapeActuelle() < 5) {
            return $this->redirectToRoute('inscription_step4');
        }

        $typesPresents = [];

        foreach ($dossier->getPiecesJustificatives() as $piece) {
            $typesPresents[] = $piece->getType();
        }

        $typesRequis = PieceJustificative::REQUIRED_TYPES;

        $piecesManquantes = array_diff($typesRequis, $typesPresents);

        return $this->render('inscription/step5.html.twig', [
            'dossier' => $dossier,
            'piecesManquantes' => $piecesManquantes,
            'step' => 5, //  OBLIGATOIRE
        ]);
    }

    // ─────────────────────────────
    // SOUMISSION FINALE
    // ─────────────────────────────

    #[Route('/soumettre', name: 'inscription_soumettre', methods: ['POST'])]
    public function soumettre(Request $request): Response
    {
        $dossier = $this->getDossierEnCours();

        if (!$this->isCsrfTokenValid('soumettre_dossier', $request->request->get('_token'))) {
            return $this->redirectToRoute('inscription_step5');
        }

        if (!$dossier->peutEtreSoumis()) {
            return $this->redirectToRoute('inscription_step5');
        }

        $dossier->setStatut(Dossier::STATUT_SOUMIS);
        $dossier->setSoumisAt(new \DateTimeImmutable());

        $this->em->flush();

        return $this->redirectToRoute('app_dashboard');
    }
}