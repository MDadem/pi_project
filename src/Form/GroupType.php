<?php

namespace App\Form;

use App\Entity\Community;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Enums\CategoryGrp;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('banner') 
            ->add('category', EnumType::class, [
                'class' => CategoryGrp::class,
                'placeholder' => 'Sélectionnez une catégorie',])
            ->add('creationDate', null, [
                'widget' => 'single_text',
            ])
           
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Community::class,
        ]);
    }
}
