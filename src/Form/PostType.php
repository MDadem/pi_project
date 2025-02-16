<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\File;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le contenu ne peut pas être vide.']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères.',
                        'max' => 1000,
                        'maxMessage' => 'Le contenu ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('postImg', FileType::class, [
                'label' => 'Upload an image',
                'mapped' => false, 
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/jpg'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or WebP or jpg)',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
