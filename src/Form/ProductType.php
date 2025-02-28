<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Capture the require_image option
        $requireImage = $options['require_image'];

        // Define constraints for the image_url field
        $imageConstraints = [
            new File([
                'maxSize' => '500k',
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, GIF).',
            ]),
        ];
        if ($requireImage) {
            $imageConstraints[] = new NotNull(['message' => 'Please upload an image.']);
        }

        $builder
            ->add('productName', TextType::class, [
                'label'    => 'Title Of Ad',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Ad title goes here',
                    'class'       => 'form-control bg-white'
                ],
            ])
            ->add('productDescription', TextareaType::class, [
                'label'    => 'Description',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Write details about your product',
                    'rows'        => 7,
                    'class'       => 'form-control bg-white'
                ],
            ])
            ->add('productPrice', NumberType::class, [
                'label'    => 'Item Price ($ USD)',
                'required' => true,
                'scale'    => 2,
                'attr'     => [
                    'placeholder' => 'Price',
                    'class'       => 'form-control bg-white',
                    'id'          => 'price'
                ],
                'constraints' => [
                    new GreaterThan(['value' => 0, 'message' => 'The price must be positive.']),
                ]
            ])
            ->add('discount', NumberType::class, [
                'label'    => 'Discount (%)',
                'required' => false,
                'scale'    => 2,
                'attr'     => [
                    'placeholder' => 'Enter discount percentage (e.g. 10 for 10%)',
                    'class'       => 'form-control bg-white'
                ],
            ])
            ->add('image_url', FileType::class, [
                'label'    => 'Upload Image',
                'mapped'   => false,
                'required' => $requireImage,
                'constraints' => $imageConstraints,
            ])
            ->add('productStock', IntegerType::class, [
                'label'    => 'Product Stock',
                'required' => true,
                'constraints' => [
                    new GreaterThan(['value' => 0, 'message' => 'Stock quantity must be positive.']),
                ],
            ])
            ->add('productCategory', EntityType::class, [
                'class' => ProductCategory::class,
                'choice_label' => 'name',
                'label' => 'Select Ad Category',
                'attr' => [
                    'class' => 'form-control bg-white',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'require_image' => true, // Default to true for new products
        ]);
    }
}