<?php

namespace App\Form\Auth;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InvitationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 20,
                ],
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'max' => 20,
                        'maxMessage' => 'Cette valeur est trop longue.
                         Elle ne doit pas dépasser {{ limit }} caractères.',
                        'minMessage' => 'Cette valeur est trop petite.
                         Elle doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'data' => $options['emailInvitation'],
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 180,
                ],
                'constraints' => [
                    new Email(),
                    new Length(['min' => 2, 'max' => 180]),
                ],
                'required' => true,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les champs du mot de passe doivent correspondre.',
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'minlength' => 8,
                        'maxlength' => 4096,
                    ],
                    'toggle' => true,
                    'hidden_label' => 'Masquer',
                    'visible_label' => 'Afficher',
                    'button_classes' => ['toggle-password-button', 'text-app-clear-black', 'dark:text-app-dark-gray2'],
                ],
                'second_options' => [
                    'label' => 'Confirmez le mot de passe',
                    'attr' => [
                        'minlength' => 8,
                        'maxlength' => 4096,
                    ],
                    'toggle' => true,
                    'hidden_label' => 'Masquer',
                    'visible_label' => 'Afficher',
                    'button_classes' => ['toggle-password-button', 'text-app-clear-black', 'dark:text-app-dark-gray2'],
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit comporter au moins {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'emailInvitation' => null,
        ]);
    }
}
