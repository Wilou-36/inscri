<?php
// src/Controller/AdminController.php

namespace App\Controller;

use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DossierRepository $dossierRepo,
        private readonly UserRepository $userRepo,
    ) {}

    // ── Tableau de bord admin ─────────────────────────────────────────────

    #[Route('', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $stats   = $this->dossierRepo->getStats();
        $recents = $this->dossierRepo->findRecents(8);
        $nbUsers = $this->userRepo->countAll();

        return $this->render('admin/dashboard.html.twig', [
            'stats'   => $stats,
            'recents' => $recents,
            'nbUsers' => $nbUsers,
        ]);
    }

    // ── Liste des candidats ───────────────────────────────────────────────

    #[Route('/candidats', name: 'admin_candidats')]
    public function candidats(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $statut = $request->query->get('statut', '');
        $bts    = $request->query->get('bts', '');

        $dossiers = $this->dossierRepo->search($search, $statut ?: null, $bts ?: null);

        return $this->render('admin/candidats.html.twig', [
            'dossiers' => $dossiers,
            'search'   => $search,
            'statut'   => $statut,
            'bts'      => $bts,
            'statuts'  => [
                'brouillon' => 'En cours',
                'soumis'    => 'Soumis',
                'valide'    => 'Validé',
                'refuse'    => 'Refusé',
            ],
            'btsList' => array_flip(Dossier::BTS_LIST),
        ]);
    }

    // ── Détail d'un dossier ───────────────────────────────────────────────

    #[Route('/dossier/{id}', name: 'admin_dossier_show')]
    public function show(Dossier $dossier): Response
    {
        return $this->render('admin/dossier_show.html.twig', [
            'dossier' => $dossier,
        ]);
    }

    // ── Changer le statut d'un dossier ───────────────────────────────────

    #[Route('/dossier/{id}/statut/{statut}', name: 'admin_dossier_statut', methods: ['POST'])]
    public function changerStatut(Dossier $dossier, string $statut, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('admin_statut_' . $dossier->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_dossier_show', ['id' => $dossier->getId()]);
        }

        $statutsValides = [Dossier::STATUT_SOUMIS, Dossier::STATUT_VALIDE, Dossier::STATUT_REFUSE];
        if (!in_array($statut, $statutsValides)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirectToRoute('admin_dossier_show', ['id' => $dossier->getId()]);
        }

        $dossier->setStatut($statut);
        $this->em->flush();

        $labels = [
            Dossier::STATUT_VALIDE => 'validé ✅',
            Dossier::STATUT_REFUSE => 'refusé ❌',
            Dossier::STATUT_SOUMIS => 'remis en attente',
        ];

        $this->addFlash('success', sprintf(
            'Le dossier de %s %s a été %s.',
            $dossier->getPrenom(),
            $dossier->getNom(),
            $labels[$statut]
        ));

        // TODO: envoyer un email de notification au candidat

        return $this->redirectToRoute('admin_dossier_show', ['id' => $dossier->getId()]);
    }

    // ── Télécharger une pièce justificative ──────────────────────────────

    #[Route('/piece/{id}/telecharger', name: 'admin_piece_download')]
    public function downloadPiece(int $id): Response
    {
        $piece = $this->em->find(\App\Entity\PieceJustificative::class, $id);

        if (!$piece || !file_exists($piece->getCheminFichier())) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($piece->getCheminFichier());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $piece->getNomFichier()
        );

        return $response;
    }

    // ── Export CSV des candidats ──────────────────────────────────────────

    #[Route('/export/csv', name: 'admin_export_csv')]
    public function exportCsv(): Response
    {
        $dossiers = $this->dossierRepo->findAllSoumis();

        $csv = "Nom;Prénom;Email;Date naissance;BTS 1er vœu;BTS 2e vœu;Apprentissage;Mention;Établissement;Statut;Date soumission\n";

        foreach ($dossiers as $d) {
            $csv .= implode(';', [
                $d->getNom() ?? '',
                $d->getPrenom() ?? '',
                $d->getUser()->getEmail(),
                $d->getDateNaissance()?->format('d/m/Y') ?? '',
                $d->getBtsChoisi() ?? '',
                $d->getBtsChoisi2() ?? '',
                $d->getRegimeAppr() ? 'Oui' : 'Non',
                $d->getMentionBac() ?? '',
                $d->getEtablissementOrigine() ?? '',
                $d->getStatutLabel(),
                $d->getSoumisAt()?->format('d/m/Y H:i') ?? '',
            ]) . "\n";
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="candidats_' . date('Y-m-d') . '.csv"');

        return $response;
    }
}
