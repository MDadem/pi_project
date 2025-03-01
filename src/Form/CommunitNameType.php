<?php

namespace App\Form;

use App\Entity\Community;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunitNameType extends AbstractType
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
        ->add('submit', SubmitType::class, [
            'label' => 'Filtrer',
            'attr' => ['class' => 'btn btn-primary']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
