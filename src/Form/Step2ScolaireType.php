<?php

/**
 * Formulaire Step2ScolaireType
 * 
 * Construit le formulaire de l'étape 2 (parcours scolaire).
 * Valide les données avec le groupe de validation 'step2'.
 * 
 * Champs:
 * - Diplôme: dernierDiplôme (obligatoire)
 * - Spécialité/Série (optionnel)
 * - Mention: mentionBac (obligatoire)
 * - Année d'obtention (obligatoire)
 * - Établissement d'origine (obligatoire)
 * - Expérience professionnelle (optionnel)
 * 
 * @author Lycée Fulbert
 * @package App\Form
 */

namespace App\Form;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Step2ScolaireType extends AbstractType
{
    /**
     * Construit les champs du formulaire
     * 
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupère l'année actuelle pour limiter les choix possibles
        $currentYear = (int) date('Y');

        $builder
            // Diplôme le plus élevé obtenu (obligatoire)
            ->add('dernierDiplome', ChoiceType::class, [
                'label'   => 'Dernier diplôme obtenu ou en cours',
                'choices' => [
                    'Baccalauréat général'        => 'bac_general',
                    'Baccalauréat technologique'  => 'bac_techno',
                    'Baccalauréat professionnel'  => 'bac_pro',
                    'BEP / CAP'                   => 'bep_cap',
                    'Autre'                       => 'autre',
                ],
            ])
            
            // Série/Spécialité du bac (ex: NSI, Maths) - optionnel
            ->add('specialiteBac', TextType::class, [
                'label'    => 'Série / Spécialité',
                'required' => false,
                'attr'     => ['placeholder' => 'NSI, Mathématiques, Terminale…'],
            ])
            
            // Mention obtenue (sans mention, assez bien, bien, très bien) - obligatoire
            ->add('mentionBac', ChoiceType::class, [
                'label'   => 'Mention obtenue ou estimée',
                'choices' => [
                    'Sans mention'              => 'passable',
                    'Assez bien'            => 'assez_bien',
                    'Bien'                  => 'bien',
                    'Très bien'             => 'tres_bien',
                    "En cours d'obtention"  => 'en_cours',
                ],
            ])
            
            // Année d'obtention - limitée entre 2000 et année actuelle + 1 (obligatoire)
            ->add('anneeObtention', IntegerType::class, [
                'label' => "Année d'obtention (ou prévue)",
                'attr'  => [
                    'min'         => 2000,
                    'max'         => $currentYear + 1,
                    'placeholder' => $currentYear,
                ],
            ])
            
            // Établissement d'origine du candidat (obligatoire)
            ->add('etablissementOrigine', TextType::class, [
                'label' => "Établissement d'origine",
                'attr'  => ['placeholder' => 'Lycée Jean Moulin, Chartres'],
            ])
            
            // Expériences professionnelles et stages (optionnel, textarea)
            ->add('experiencePro', TextareaType::class, [
                'label'    => 'Expériences professionnelles (stages, jobs…)',
                'required' => false,
                'attr'     => ['rows' => 4, 'placeholder' => 'Stage de 2 semaines en entreprise…'],
            ]);
    }

    /**
     * Configure les options du formulaire
     * 
     * @param OptionsResolver $resolver Résolveur d'options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Dossier::class,
            'validation_groups' => ['Default', 'step2'], // Groupe de validation step2
        ]);
    }
}

        $builder
            ->add('dernierDiplome', ChoiceType::class, [
                'label'   => 'Dernier diplôme obtenu ou en cours',
                'choices' => [
                    'Baccalauréat général'        => 'bac_general',
                    'Baccalauréat technologique'  => 'bac_techno',
                    'Baccalauréat professionnel'  => 'bac_pro',
                    'BEP / CAP'                   => 'bep_cap',
                    'Autre'                       => 'autre',
                ],
            ])
            ->add('specialiteBac', TextType::class, [
                'label'    => 'Série / Spécialité',
                'required' => false,
                'attr'     => ['placeholder' => 'NSI, Mathématiques, Terminale…'],
            ])
            ->add('mentionBac', ChoiceType::class, [
                'label'   => 'Mention obtenue ou estimée',
                'choices' => [
                    'Sans mention'              => 'passable',
                    'Assez bien'            => 'assez_bien',
                    'Bien'                  => 'bien',
                    'Très bien'             => 'tres_bien',
                    "En cours d'obtention"  => 'en_cours',
                ],
            ])
            ->add('anneeObtention', IntegerType::class, [
                'label' => "Année d'obtention (ou prévue)",
                'attr'  => [
                    'min'         => 2000,
                    'max'         => $currentYear + 1,
                    'placeholder' => $currentYear,
                ],
            ])
            ->add('etablissementOrigine', TextType::class, [
                'label' => "Établissement d'origine",
                'attr'  => ['placeholder' => 'Lycée Jean Moulin, Chartres'],
            ])
            ->add('experiencePro', TextareaType::class, [
                'label'    => 'Expériences professionnelles (stages, jobs…)',
                'required' => false,
                'attr'     => ['rows' => 4, 'placeholder' => 'Stage de 2 semaines en entreprise…'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Dossier::class,
            'validation_groups' => ['Default', 'step2'],
        ]);
    }
}
