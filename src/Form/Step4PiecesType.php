<?php

/**
 * Formulaire Step4PiecesType
 * 
 * Construit le formulaire de l'étape 4 (upload des pièces justificatives).
 * Gère l'upload de fichiers (PDF, JPG, PNG) pour les pièces obligatoires et optionnelles.
 * 
 * Pièces obligatoires:
 * - Carte d'identité (CNI)
 * - Photo d'identité
 * - Relevé de notes
 * 
 * Pièces optionnelles:
 * - Diplômes supplémentaires
 * - Certificats de travail
 * - Etc.
 * 
 * Contraintes:
 * - Formats: PDF, JPG, PNG uniquement
 * - Taille maximale: 5 Mo par fichier
 * 
 * @author Lycée Fulbert
 * @package App\Form
 */

namespace App\Form;

use App\Entity\PieceJustificative;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class Step4PiecesType extends AbstractType
{
    /**
     * Construit les champs du formulaire
     * 
     * Crée dynamiquement un champ FileType pour chaque type de pièce justificative.
     * Les pièces obligatoires sont marquées avec un *.
     * 
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Définit les types de pièces obligatoires
        $required = ['cni', 'photo', 'releve_notes'];

        // Crée un champ pour chaque type de pièce justificative
        foreach (PieceJustificative::TYPES as $key => $label) {
            // Vérifie si cette pièce est obligatoire
            $isRequired = in_array($key, $required);

            // Ajoute le champ d'upload
            $builder->add($key, FileType::class, [
                'label'    => $label . ($isRequired ? ' *' : ''), // Ajoute * si obligatoire
                'mapped'   => false, // N'est pas lié directement à l'entité
                'required' => false, // Permettre la soumission même sans fichier (on vérifie côté contrôleur)
                
                // Restriction des types MIME acceptés
                'attr'     => ['accept' => 'application/pdf,image/jpeg,image/png'],
                
                // Message d'aide affiché sous le champ
                'help'     => 'PDF, JPG ou PNG — max 5 Mo' . ($isRequired ? ' (obligatoire)' : ' (facultatif)'),
                
                // Contraintes de validation du fichier
                'constraints' => [
                    new File([
                        'maxSize'          => '5M', // Limite de taille
                        'mimeTypes'        => ['application/pdf', 'image/jpeg', 'image/png'], // Formats autorisés
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF, JPG ou PNG.',
                        'maxSizeMessage'   => 'Le fichier ne doit pas dépasser 5 Mo.',
                    ]),
                ],
            ]);
        }
    }

    /**
     * Configure les options du formulaire
     * 
     * @param OptionsResolver $resolver Résolveur d'options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // Ce formulaire n'est pas lié à une entité
        ]);
    }
}

        foreach (PieceJustificative::TYPES as $key => $label) {
            $isRequired = in_array($key, $required);

            $builder->add($key, FileType::class, [
                'label'    => $label . ($isRequired ? ' *' : ''),
                'mapped'   => false,
                'required' => false,
                'attr'     => ['accept' => 'application/pdf,image/jpeg,image/png'],
                'help'     => 'PDF, JPG ou PNG — max 5 Mo' . ($isRequired ? ' (obligatoire)' : ' (facultatif)'),
                'constraints' => [
                    new File([
                        'maxSize'          => '5M',
                        'mimeTypes'        => ['application/pdf', 'image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF, JPG ou PNG.',
                        'maxSizeMessage'   => 'Le fichier ne doit pas dépasser 5 Mo.',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
