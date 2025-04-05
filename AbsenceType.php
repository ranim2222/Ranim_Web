<?php

namespace App\Form;

use App\Entity\Absence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class AbsenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Date', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('nbr_abs', TextType::class, [])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Justifiée' => 'justifiee',
                    'Non justifiée' => 'non_justifiee',
                ],
                'placeholder' => 'Sélectionner un type',
            ])
            ->add('cin', TextType::class, [])
            ->add('image_path', FileType::class, [
                'label' => 'Télécharger une image',
                'mapped' => false, // Empêche Symfony de lier ce champ directement à l'entité
                'required' => false, // Image facultative
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Absence::class,
        ]);
    }
}
