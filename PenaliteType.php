<?php

namespace App\Form;

use App\Entity\Penalite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class PenaliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Avertissement écrit' => 'Avertissement écrit',
                    'Suspension temporaire' => 'Suspension temporaire',
                    'Démotion' => 'Démotion',
                ],
                'placeholder' => 'Sélectionner un type',
                // Supprimer les contraintes de validation si tu ne veux plus de messages d'erreur ici
                // 'constraints' => [
                //     new NotBlank(['message' => 'Le type de pénalité est obligatoire.']),
                // ],
            ])
            ->add('seuil_abs', TextType::class, [
                // Si tu veux éviter les alertes, tu peux également désactiver les contraintes
                // 'constraints' => [
                //     new NotBlank(['message' => 'Le seuil d\'absence est obligatoire.']),
                //     new Regex([
                //         'pattern' => '/^\d+$/',
                //         'message' => 'Le seuil d\'absence doit être un nombre entier.',
                //     ]),
                // ],
            ])
            ->add('cin', ChoiceType::class, [
                'choices' => $options['cin_choices'],  // Utilisation de la liste des CIN passés depuis le contrôleur
                'placeholder' => 'Sélectionner un CIN',
                // 'constraints' => [
                //     new NotBlank(['message' => 'Le CIN est obligatoire.']),
                // ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Penalite::class,
            'cin_choices' => [], // Cette option permet de passer la liste des CIN depuis le contrôleur
        ]);
    }
}
