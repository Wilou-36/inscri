<?php

/**
 * Formulaire Step1PersonnelType
 * 
 * Construit le formulaire de l'étape 1 (informations personnelles).
 * Valide les données avec le groupe de validation 'step1'.
 * 
 * Champs:
 * - Identité: nom, prenom, genre, dateNaissance, nationalite
 * - Contact: telephone
 * - Adresse: adresse, codePostal, ville
 * 
 * @author Lycée Fulbert
 * @package App\Form
 */

namespace App\Form;

use App\Entity\Dossier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Step1PersonnelType extends AbstractType
{
    /**
     * Construit les champs du formulaire
     * 
     * @param FormBuilderInterface $builder Constructeur de formulaire
     * @param array $options Options du formulaire
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 📝 NOM - Texte simple, obligatoire
            ->add('nom', TextType::class, [
                'label' => 'Nom de famille',
                'attr'  => ['placeholder' => 'DUPONT'],
            ])
            
            // 👤 PRENOM - Texte simple, obligatoire
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr'  => ['placeholder' => 'Marie'],
            ])
            
            // 👥 GENRE - Radio buttons (Madame/Monsieur/Autre), obligatoire
            ->add('genre', ChoiceType::class, [
                'label'    => 'Civilité',
                'choices'  => ['Madame' => 'F', 'Monsieur' => 'M', 'Autre / Non précisé' => 'N'],
                'expanded' => true, // ✅ Affiche les radio buttons
                'required' => true, // ✅ Forcément obligatoire
            ])
            
            // 🎂 DATE DE NAISSANCE - Type date, doit être dans le passé
            ->add('dateNaissance', BirthdayType::class, [
                'label'  => 'Date de naissance',
                'widget' => 'single_text', // 📅 Calendrier HTML5
                // ⚠️ Limite maximum: 15 ans révolus
                'attr'   => ['max' => (new \DateTime('-15 years'))->format('Y-m-d')],
            ])
            
            // 🌍 NATIONALITE - Texte libre, obligatoire
            ->add('nationalite', TextType::class, [
                'label' => 'Nationalité',
                'attr'  => ['placeholder' => 'Française'],
            ])
            
            // 📱 TELEPHONE - Type tel, format: XX XX XX XX XX (10 chiffres)
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone mobile',
                'attr'  => ['placeholder' => '06 12 34 56 78'],
                // ⚠️ JavaScript formate automatiquement en "06 12 34 56 78"
            ])
            
            // 🏠 ADRESSE - Texte libre, obligatoire
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'attr'  => ['placeholder' => '12 rue de la Paix'],
            ])
            
            // 📮 CODE POSTAL - Exactement 5 chiffres, obligatoire
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'attr'  => ['placeholder' => '28000', 'maxlength' => 5],
            ])
            
            // 🏘️ VILLE - Texte libre, obligatoire
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'attr'  => ['placeholder' => 'Chartres'],
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
            'data_class'        => Dossier::class, // ✅ Lie au Dossier
            'validation_groups' => ['Default', 'step1'], // ✅ Groupe de validation
        ]);
    }
}
