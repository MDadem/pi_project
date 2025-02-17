<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'empty_data' =>'',
                'attr' => ['required' => false], // Disables HTML 'required'
            ])
            ->add('lastName', TextType::class, [
                'empty_data' =>'',
                'attr' => ['required' => false],
            ])
            ->add('email', EmailType::class, [
                'empty_data' =>'',
                'attr' => ['required' => false],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'empty_data' =>'',
                'mapped' => false,
                'label' => false, // Prevents Symfony from generating a second label
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
                'attr' => ['class' => 'input-checkbox100', 'required' => false]
            ])
            ->add('password', PasswordType::class, [
                'empty_data' =>'',
                'label' => 'Password',
                'mapped' => true,
                'attr' => ['required' => false],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
