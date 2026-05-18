<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espace')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $dossiers = $em->getRepository(Dossier::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $dossierActif = null;
        foreach ($dossiers as $d) {
            if ($d->getStatut() === Dossier::STATUT_BROUILLON) {
                $dossierActif = $d;
                break;
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'user'         => $user,
            'dossiers'     => $dossiers,
            'dossierActif' => $dossierActif,
        ]);
    }
}
