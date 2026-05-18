<?php

/**
 * Formulaire Step3BtsType
 * 
 * Construit le formulaire de l'étape 3 (choix du BTS et lettre de motivation).
 * Valide les données avec le groupe de validation 'step3'.
 * 
 * Champs:
 * - BTS principal (obligatoire): 6 options disponibles
 * - BTS secondaire (optionnel): pour un second vœu
 * - Régime apprentissage (optionnel): checkbox
 * - Lettre de motivation (obligatoire): min 200 caractères
 * 
 * @author Lycée Fulbert
 * @package App\Form
 */

namespace App\Form;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Step3BtsType extends AbstractType
{
    /**
     * Construit les champs du formulaire
     * 
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupère la liste des BTS disponibles depuis l'entité Dossier
        $btsChoices = Dossier::BTS_LIST;

        $builder
            // BTS principal - Le candidat doit sélectionner son premier vœu (obligatoire)
            ->add('btsChoisi', ChoiceType::class, [
                'label'    => 'Premier vœu (BTS souhaité)',
                'choices'  => $btsChoices,
                'expanded' => true, // Radio buttons plutôt que dropdown
                'required' => true,
            ])
            
            // BTS secondaire - Deuxième vœu optionnel (peut choisir "Aucun")
            ->add('btsChoisi2', ChoiceType::class, [
                'label'    => 'Deuxième vœu (optionnel)',
                'choices'  => array_merge(['Aucun deuxième vœu' => ''], $btsChoices),
                'required' => false,
            ])
            
            // Régime d'apprentissage - Checkbox pour indiquer le souhait d'apprentissage
            ->add('regimeAppr', CheckboxType::class, [
                'label'    => "Je souhaite m'inscrire en apprentissage",
                'required' => false,
            ])
            
            // Lettre de motivation - Textarea obligatoire, minimum 200 caractères
            ->add('lettreMotivation', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'attr'  => [
                    'rows'        => 10,
                    'placeholder' => 'Expliquez en quoi ce BTS correspond à votre projet professionnel (minimum 200 caractères)…',
                ],
                'help'  => 'Minimum 200 caractères. Expliquez votre motivation et votre projet.',
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
            'validation_groups' => ['Default', 'step3'], // Groupe de validation step3
        ]);
    }
}

        $builder
            ->add('btsChoisi', ChoiceType::class, [
                'label'    => 'Premier vœu (BTS souhaité)',
                'choices'  => $btsChoices,
                'expanded' => true,
                'required' => true,
            ])
            ->add('btsChoisi2', ChoiceType::class, [
                'label'    => 'Deuxième vœu (optionnel)',
                'choices'  => array_merge(['Aucun deuxième vœu' => ''], $btsChoices),
                'required' => false,
            ])
            ->add('regimeAppr', CheckboxType::class, [
                'label'    => "Je souhaite m'inscrire en apprentissage",
                'required' => false,
            ])
            ->add('lettreMotivation', TextareaType::class, [
                'label' => 'Lettre de motivation',
                'attr'  => [
                    'rows'        => 10,
                    'placeholder' => 'Expliquez en quoi ce BTS correspond à votre projet professionnel (minimum 200 caractères)…',
                ],
                'help'  => 'Minimum 200 caractères. Expliquez votre motivation et votre projet.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Dossier::class,
            'validation_groups' => ['Default', 'step3'],
        ]);
    }
}