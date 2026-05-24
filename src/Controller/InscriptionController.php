<?php

namespace App\Controller;

use App\Entity\Dossier;
use App\Entity\PieceJustificative;
use App\Form\Step1PersonnelType;
use App\Form\Step2ScolaireType;
use App\Form\Step3BtsType;
use App\Form\Step4PiecesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inscription')]
#[IsGranted('ROLE_USER')]
class InscriptionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    // ─────────────────────────────
    //  RÉCUPÉRER DOSSIER
    // ─────────────────────────────

    private function getDossierEnCours(): Dossier
    {
        $user    = $this->getUser();
        $dossier = $this->em->getRepository(Dossier::class)->findOneBy(['user' => $user]);

        if (!$dossier) {
            $dossier = new Dossier();
            $dossier->setUser($user);
            $dossier->setStatut(Dossier::STATUT_BROUILLON);
            $dossier->setEtapeActuelle(1);
            $this->em->persist($dossier); // obligatoire pour un nouvel objet
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
            if ($dossier->getEtapeActuelle() < 2) {
                $dossier->setEtapeActuelle(2);
            }
            $this->em->flush();
            $this->addFlash('success', 'Informations personnelles enregistrées.');
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
            if ($dossier->getEtapeActuelle() < 3) {
                $dossier->setEtapeActuelle(3);
            }
            $this->em->flush();
            $this->addFlash('success', 'Parcours scolaire enregistré.');
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
            if ($dossier->getEtapeActuelle() < 4) {
                $dossier->setEtapeActuelle(4);
            }
            $this->em->flush();
            $this->addFlash('success', 'Choix du BTS enregistré.');
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

                    // récupérer les métadonnées AVANT le move()
                    $mimeType = $file->getMimeType();
                    $size     = $file->getSize();

                    // supprimer l'ancienne pièce si elle existe
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
                    $piece->setMimeType($mimeType);
                    $piece->setTaille($size);
                    $piece->setDossier($dossier);

                    $this->em->persist($piece);
                }
            }

            if ($dossier->getEtapeActuelle() < 5) {
                $dossier->setEtapeActuelle(5);
            }
            $this->em->flush();
            $this->addFlash('success', 'Pièces justificatives enregistrées.');
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

        $typesPresents    = array_map(fn($p) => $p->getType(), $dossier->getPiecesJustificatives()->toArray());
        $piecesManquantes = array_diff(PieceJustificative::REQUIRED_TYPES, $typesPresents);

        return $this->render('inscription/step5.html.twig', [
            'dossier'          => $dossier,
            'piecesManquantes' => $piecesManquantes,
            'step'             => 5,
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

        $this->addFlash('success', 'Votre dossier a été soumis avec succès.');

        return $this->redirectToRoute('app_dashboard');
    }
}