<?php
// src/DataFixtures/AppFixtures.php

namespace App\DataFixtures;

use App\Entity\Dossier;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ── Admin ────────────────────────────────────────────────────────
        $admin = new User();
        $admin->setEmail('admin@fulbert.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'BTS_Admin#28'));
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // ── Candidats de test ────────────────────────────────────────────
        $candidates = [
            ['Marie', 'DUPONT',    'marie.dupont@email.fr',    Dossier::STATUT_SOUMIS,    'bts_sio_slam', 4],
            ['Thomas', 'MARTIN',   'thomas.martin@email.fr',   Dossier::STATUT_BROUILLON, 'bts_sio_sisr', 2],
            ['Emma',  'LEROY',     'emma.leroy@email.fr',      Dossier::STATUT_VALIDE,    'bts_mco',      5],
            ['Lucas', 'BERNARD',   'lucas.bernard@email.fr',   Dossier::STATUT_SOUMIS,    'bts_gpme',     4],
            ['Inès',  'MOREAU',    'ines.moreau@email.fr',     Dossier::STATUT_REFUSE,    'bts_compta',   5],
            ['Hugo',  'SIMON',     'hugo.simon@email.fr',      Dossier::STATUT_BROUILLON, null,           1],
        ];

        foreach ($candidates as [$prenom, $nom, $email, $statut, $bts, $etape]) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword($this->hasher->hashPassword($user, 'Test1234!'));
            $user->setIsVerified(true);
            $manager->persist($user);

            $dossier = new Dossier();
            $dossier->setUser($user);
            $dossier->setStatut($statut);
            $dossier->setEtapeActuelle($etape);

            if ($etape >= 1) {
                $dossier->setPrenom($prenom);
                $dossier->setNom($nom);
                $dossier->setGenre($prenom === 'Marie' || $prenom === 'Emma' || $prenom === 'Inès' ? 'F' : 'M');
                $dossier->setDateNaissance(new \DateTime('-19 years'));
                $dossier->setNationalite('Française');
                $dossier->setTelephone('06 12 34 56 78');
                $dossier->setAdresse('12 rue de la Paix');
                $dossier->setCodePostal('28 000');
                $dossier->setVille('Chartres');
            }
            if ($etape >= 2) {
                $dossier->setDernierDiplome('bac_general');
                $dossier->setMentionBac('bien');
                $dossier->setAnneeObtention(2024);
                $dossier->setEtablissementOrigine('Lycée Jean Moulin, Chartres');
                $dossier->setSpecialiteBac('NSI, Mathématiques');
            }
            if ($etape >= 3 && $bts) {
                $dossier->setBtsChoisi($bts);
                $dossier->setLettreMotivation(
                    "Passionné(e) par l'informatique depuis plusieurs années, je souhaite intégrer ce BTS " .
                    "afin de développer des compétences professionnelles solides dans le domaine du numérique. " .
                    "Mon parcours scolaire m'a permis d'acquérir des bases en algorithmique et en développement web, " .
                    "que je souhaite approfondir au sein du lycée Fulbert, reconnu pour la qualité de ses formations."
                );
                $dossier->setRegimeAppr(false);
            }
            if ($statut === Dossier::STATUT_SOUMIS || $statut === Dossier::STATUT_VALIDE || $statut === Dossier::STATUT_REFUSE) {
                $dossier->setSoumisAt(new \DateTimeImmutable('-' . rand(1, 14) . ' days'));
            }

            $manager->persist($dossier);
        }

        $manager->flush();
    }
}
