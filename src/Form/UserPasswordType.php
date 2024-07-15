<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', PasswordType::class, [
                'attr' => [
                    'minlength' => 8,
                    'maxlength' => 4096,
                ],
                'required' => true,
                'toggle' => true,
                'hidden_label' => new TranslatableMessage('user.form.hide', [], 'app'),
                'visible_label' => new TranslatableMessage('user.form.show', [], 'app'),
                'button_classes' => ['toggle-password-button', 'text-app-clear-black', 'dark:text-app-dark-gray2'],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 8,
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => new TranslatableMessage('user.form.invalid_password', [], 'app'),
                'required' => true,
                'first_options' => [
                    'label' => new TranslatableMessage('user.form.password', [], 'app'),
                    'attr' => [
                        'minlength' => 8,
                        'maxlength' => 4096,
                    ],
                    'toggle' => true,
                    'hidden_label' => new TranslatableMessage('user.form.hide', [], 'app'),
                    'visible_label' => new TranslatableMessage('user.form.show', [], 'app'),
                    'button_classes' => ['toggle-password-button', 'text-app-clear-black', 'dark:text-app-dark-gray2'],
                ],
                'second_options' => [
                    'label' => new TranslatableMessage('user.form.new_password_confirm', [], 'app'),
                    'attr' => [
                        'minlength' => 8,
                        'maxlength' => 4096,
                    ],
                    'toggle' => true,
                    'hidden_label' => new TranslatableMessage('user.form.hide', [], 'app'),
                    'visible_label' => new TranslatableMessage('user.form.show', [], 'app'),
                    'button_classes' => ['toggle-password-button', 'text-app-clear-black', 'dark:text-app-dark-gray2'],
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 8,
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
            // Configure your form options here
        ]);
    }
}
