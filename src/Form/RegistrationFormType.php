<?php

/**
 * Formulaire d'enregistrement des utilisateurs (inscription).
 * 
 * Ce formulaire permet aux nouveaux utilisateurs de s'inscrire avec :
 * - Une adresse email
 * - Un mot de passe (avec confirmation)
 * - L'acceptation des conditions générales d'utilisation
 * 
 * Validations appliquées :
 * - Email: format email valide
 * - Mot de passe: minimum 8 caractères, une majuscule, une minuscule et un chiffre
 * - CGU: obligatoire
 */

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Classe RegistrationFormType
 * Responsable de la construction et de la configuration du formulaire d'inscription
 */
class RegistrationFormType extends AbstractType
{
    /**
     * Construit le formulaire d'enregistrement.
     * 
     * @param FormBuilderInterface $builder Le constructeur de formulaire
     * @param array $options Les options du formulaire
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr'  => ['placeholder' => 'votre@email.fr', 'autocomplete' => 'email'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'first_options'   => [
                    'label' => 'Mot de passe',
                    'attr'  => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                        new Assert\Length([
                            'min'        => 8,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        ]),
                        new Assert\Regex([
                            'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                            'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.',
                        ]),
                    ],
                ],
                'second_options'  => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
            ])
            ->add('acceptCgu', CheckboxType::class, [
                'mapped'      => false,
                'label'       => "J'accepte les conditions générales d'utilisation",
                'constraints' => [
                    new Assert\IsTrue(['message' => 'Vous devez accepter les CGU.']),
                ],
            ]);
    }

    /**
     * Configure les options du formulaire.
     * 
     * @param OptionsResolver $resolver L'analyseur d'options
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Associe le formulaire à la classe User
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
