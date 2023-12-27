<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserPasswordType;
use App\Form\UserType;
use App\Security\Auth\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'user_index')]
    public function index(Request $request, Security $security, UserPasswordHasherInterface $passwordHasher): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $currentEmail = $user->getEmail();

        // Create the forms
        $formUser = $this->createForm(UserType::class, $user);
        $formUserPassword = $this->createForm(UserPasswordType::class);

        $formUser->handleRequest($request);
        $formUserPassword->handleRequest($request);

        // Handle formUser
        if ($formUser->isSubmitted() && $formUser->isValid()) {
            $data = $formUser->getData();

            $this->em->flush();
            $this->addFlash(
                'success',
                'Vos informations ont été éditées avec succès !'
            );

            // Login the user if the email is edited
            if (0 !== strcmp($data->getEmail(), $currentEmail)) {
                $security->login($user, AppAuthenticator::class);
            }

            return $this->redirectToRoute('user_index', [
                'tabSelected' => 2,
            ]);
        }

        // Handle formUserPassword
        if ($formUserPassword->isSubmitted() && $formUserPassword->isValid()) {
            $data = $formUserPassword->getData();

            // Check if password is valid
            if ($passwordHasher->isPasswordValid($user, $data['plainPassword'])) {
                $user->setPlainPassword($data['newPassword']);

                // To trigger the UserEntityListener
                $user->setUpdatedAt(new \DateTimeImmutable());

                $this->em->flush();
                $this->addFlash(
                    'success',
                    'Le mot de passe a été édité avec success !'
                );

                return $this->redirectToRoute('user_index');
            } else {
                $this->addFlash(
                    'warning',
                    'Le mot de passe renseigné est incorrect.'
                );
            }

            return $this->redirectToRoute('user_index', [
                'tabSelected' => 3,
            ]);
        }

        return $this->render('pages/user/index.html.twig', [
            'user' => $user,
            'userForm' => $formUser,
            'passwordForm' => $formUserPassword,
        ]);
    }
}
