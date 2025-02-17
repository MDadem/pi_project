<?php

namespace App\Form;

use App\Entity\Product;
use App\Enum\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\DataTransformer\CategoryToStringTransformer;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThan;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Product Name (Title of Ad)
            ->add('productName', TextType::class, [
                'label'    => 'Title Of Ad',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Ad title goes here',
                    'class'       => 'form-control bg-white'
                ],
            ])
            // Description
            ->add('productDescription', TextareaType::class, [
                'label'    => 'Description',
                'required' => true,
                'attr'     => [
                    'placeholder' => 'Write details about your product',
                    'rows'        => 7,
                    'class'       => 'form-control bg-white'
                ],
            ])
            // Price
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
            // Image Upload (using FileType instead of UrlType)
            ->add('image_url', FileType::class, [
                'label'    => 'Upload Image',
                'mapped'   => false, // Prevent automatic mapping to the entity (handled in controller)
                'required' => true,
                'constraints' => [
                    new NotNull(['message' => 'Please upload an image.']),
                    new File([
                        'maxSize' => '500k',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, GIF).',
                    ]),
                ],
            ])
            // Product Stock
            ->add('productStock', IntegerType::class, [
                'label'    => 'Product Stock',
                'required' => true,
                'constraints' => [
                    new GreaterThan(['value' => 0, 'message' => 'Stock quantity must be positive.']),
                ],
            ])
            // Categories Selection (Multiple)
            ->add('categories', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn(Category $category) => ucfirst($category->value), Category::cases()), 
                    array_map(fn(Category $category) => $category->value, Category::cases())
                ),
                'multiple' => true, 
                'expanded' => false,
                'label'    => 'Select Categories',
                'required' => true,
                'data'     => [Category::ELECTRONICS],
            ]);

        // Apply a data transformer for categories if needed.
        $builder->get('categories')->addModelTransformer(new CategoryToStringTransformer());

        // Note: The "owner" field is not included in the form.
        // It is set in the controller based on the logged-in user.
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
