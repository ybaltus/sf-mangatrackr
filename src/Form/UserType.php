<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
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
                    ]),
                ],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
