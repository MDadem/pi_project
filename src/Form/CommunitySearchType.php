<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Community;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use App\Enums\CategoryGrp;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CommunitySearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('community', EntityType::class, [
            'class' => Community::class, // Link to the Community entity
            'choice_label' => 'name', 
            'placeholder' => 'Select a community', // Optional: default text
            'required' => false, // Set to false if you want an optional selection
        ])
        ->add('startDate', DateType::class, [
            'widget' => 'single_text',
            'required' => false,
            'label' => 'Date de début'
        ])
        ->add('endDate', DateType::class, [
            'widget' => 'single_text',
            'required' => false,
            'label' => 'Date de fin'
        ])
        ->add('category', EnumType::class, [
            'class' => CategoryGrp::class,
            'placeholder' => 'Sélectionnez une catégorie',
            'required' => false,
            
        ])

        ->add('search', SubmitType::class, [
            'label' => 'Rechercher',])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
