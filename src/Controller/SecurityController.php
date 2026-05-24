<?php

// src/Controller/SecurityController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    // =========================================================================
    // CONNEXION
    // Affiche le formulaire de connexion et redirige selon le rôle.
    //
    // Flux de redirection après connexion :
    //   ROLE_ADMIN  → admin_dashboard  (/admin/tableau-de-bord)
    //   ROLE_USER   → app_dashboard    (/mon-espace)
    // =========================================================================

    #[Route('/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Utilisateur déjà connecté : redirection immédiate selon le rôle
        if ($this->getUser()) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('admin_dashboard')
                : $this->redirectToRoute('app_dashboard');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    // =========================================================================
    // DÉCONNEXION
    // Route interceptée automatiquement par le firewall Symfony (security.yaml).
    // Aucune logique à écrire ici — Symfony vide la session et redirige.
    // =========================================================================

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        // Intercepté par le firewall — cette ligne n'est jamais atteinte
        throw new \LogicException('Cette route est gérée par le firewall Symfony.');
    }

    // =========================================================================
    // CRÉATION DE COMPTE (candidats uniquement)
    // Réservé aux nouveaux candidats souhaitant déposer un dossier BTS.
    //
    // Le compte administrateur ne peut PAS être créé via ce formulaire :
    //   → le rôle ROLE_ADMIN est attribué exclusivement via la commande console
    //      `php bin/console app:create-admin`
    //
    // Après inscription réussie :
    //   → un token de vérification email est généré
    //   → le rôle ROLE_USER est forcé (jamais ROLE_ADMIN)
    //   → l'utilisateur est redirigé vers la page de connexion
    // =========================================================================

    #[Route('/creer-un-compte', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $em
    ): Response {
        // Utilisateur déjà connecté : redirection immédiate selon le rôle
        if ($this->getUser()) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('admin_dashboard')
                : $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Hash sécurisé du mot de passe (algorithme défini dans security.yaml)
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            // Génération du token de vérification email (64 caractères hex)
            $user->setEmailVerificationToken(bin2hex(random_bytes(32)));

            // Rôle candidat uniquement — jamais ROLE_ADMIN via ce formulaire
            $user->setRoles(['ROLE_USER']);

            $em->persist($user);
            $em->flush();

            // TODO : déclencher l'envoi de l'email de vérification
            // $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, ...);

            $this->addFlash('success', 'Votre compte a été créé. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    // =========================================================================
    // VÉRIFICATION DE L'ADRESSE EMAIL
    // Activée en cliquant sur le lien reçu par email après inscription.
    //
    // Le token est à usage unique : il est effacé dès validation.
    // Si le token est invalide ou expiré → message d'erreur + retour connexion.
    // =========================================================================

    #[Route('/verification-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)
                   ->findOneBy(['emailVerificationToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Marquer l'email comme vérifié et invalider le token (usage unique)
        $user->setIsVerified(true);
        $user->setEmailVerificationToken(null);
        $em->flush();

        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée. Bienvenue !');
        return $this->redirectToRoute('app_dashboard');
    }

    // =========================================================================
    // PAGES LÉGALES
    // Accessibles publiquement (sans authentification).
    // Centralisées ici pour garder toutes les routes non-métier au même endroit.
    //
    // À déclarer dans security.yaml :
    //   access_control:
    //     - { path: ^/conditions-generales-utilisation, roles: PUBLIC_ACCESS }
    //     - { path: ^/politique-de-confidentialite,     roles: PUBLIC_ACCESS }
    // =========================================================================

    /**
     * Conditions Générales d'Utilisation
     * Lien depuis : security/register.html.twig
     */
    #[Route('/conditions-generales-utilisation', name: 'app_cgu')]
    public function cgu(): Response
    {
        return $this->render('legal/cgu.html.twig');
    }

    /**
     * Politique de confidentialité (RGPD)
     * Lien depuis : security/register.html.twig, legal/politique_confidentialite.html.twig
     *
     * Nom de route : app_privacy
     * (cohérent avec tous les {{ path('app_privacy') }} dans les templates)
     */
    #[Route('/politique-de-confidentialite', name: 'app_privacy')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('legal/app_privacy.html.twig');
    }
}