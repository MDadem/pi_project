<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\ProductCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $requireImage = $options['require_image'];

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
                'label' => 'Title Of Ad',
                'required' => true,
                'attr' => ['placeholder' => 'Ad title goes here', 'class' => 'form-control bg-white'],
            ])
            ->add('productDescription', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['placeholder' => 'Write details about your product', 'rows' => 7, 'class' => 'form-control bg-white'],
            ])
            ->add('productPrice', NumberType::class, [
                'label' => 'Item Price ($ USD)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'Price (optional if using dynamic pricing)', 'class' => 'form-control bg-white', 'id' => 'price'],
                'constraints' => [
                    new GreaterThan(['value' => 0, 'message' => 'The price must be positive.']),
                ],
            ])
            ->add('discount', NumberType::class, [
                'label' => 'Discount (%)',
                'required' => false,
                'scale' => 2,
                'attr' => ['placeholder' => 'Enter discount percentage (e.g. 10 for 10%)', 'class' => 'form-control bg-white', 'id' => 'product_discount'],
            ])
            ->add('image_url', FileType::class, [
                'label' => 'Upload Image',
                'mapped' => false,
                'required' => $requireImage,
                'constraints' => $imageConstraints,
            ])
            ->add('productStock', IntegerType::class, [
                'label' => 'Product Stock',
                'required' => true,
                'attr' => ['class' => 'form-control bg-white'],
                'constraints' => [new GreaterThan(['value' => 0, 'message' => 'Stock quantity must be positive.'])],
            ])
            ->add('productCategory', EntityType::class, [
                'class' => ProductCategory::class,
                'choice_label' => 'name',
                'label' => 'Select Ad Category',
                'attr' => ['class' => 'form-control bg-white'],
            ])
            ->add('useDynamicPricing', CheckboxType::class, [
                'label' => 'Use Dynamic Pricing (AI adjusts price based on stock)',
                'required' => false,
                'attr' => ['class' => 'form-check-input', 'id' => 'product_useDynamicPricing'],
            ]);

        // Custom validation for price vs dynamic pricing
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $product = $form->getData();

                if (!$product->getUseDynamicPricing() && $product->getProductPrice() === null) {
                    $form->get('productPrice')->addError(new \Symfony\Component\Form\FormError('Product price is required unless dynamic pricing is enabled.'));
                }
            }
        );

        // Enforce: No discount when dynamic pricing is enabled
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $product = $event->getData();
                $form = $event->getForm();

                if ($product && $product->getUseDynamicPricing()) {
                    $form->add('discount', NumberType::class, [
                        'label' => 'Discount (%)',
                        'required' => false,
                        'disabled' => true,
                        'data' => null,
                        'attr' => ['placeholder' => 'Disabled with Dynamic Pricing', 'class' => 'form-control bg-white', 'id' => 'product_discount', 'readonly' => true],
                    ]);
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if (isset($data['useDynamicPricing']) && $data['useDynamicPricing']) {
                    $data['discount'] = null; // Force discount to null
                    $event->setData($data);
                    $form->add('discount', NumberType::class, [
                        'label' => 'Discount (%)',
                        'required' => false,
                        'disabled' => true,
                        'data' => null,
                        'attr' => ['placeholder' => 'Disabled with Dynamic Pricing', 'class' => 'form-control bg-white', 'id' => 'product_discount', 'readonly' => true],
                    ]);
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'require_image' => true,
        ]);
    }
}