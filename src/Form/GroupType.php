<?php

namespace App\Form;

use App\Entity\Community;
use App\Enums\CategoryGrp;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;


class GroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du groupe',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du groupe est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'max' => 50,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire.']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                        'max' => 500,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.'
                    ]),
                ],
            ])
            ->add('banner', FileType::class, [
                'label' => 'Bannière (image)',
                'required' => true,
                'mapped' => false, // Indique que ce champ ne correspond pas directement à une propriété de l'entité
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L’image de la bannière est obligatoire.']),
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpg'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or WebP or jpg)',
                    ]),
                ],
            ])
            ->add('category', EnumType::class, [
                'class' => CategoryGrp::class,
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une catégorie.']),
                ],
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
