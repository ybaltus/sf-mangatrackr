<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Security\Auth\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'user_index')]
    public function index(Request $request, Security $security): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $currentEmail = $user->getEmail();

        $formUser = $this->createForm(UserType::class, $user);

        // TODO password form

        $formUser->handleRequest($request);

        // Handle formUser
        if ($formUser->isSubmitted() && $formUser->isValid()) {
            $data = $formUser->getData();

            $this->em->flush();
            $this->addFlash(
                'successUserEditInformations',
                'Vos informations ont été éditées avec succès !'
            );

            // Login the user if the email is edited
            if (0 !== strcmp($data->getEmail(), $currentEmail)) {
                $security->login($user, AppAuthenticator::class);
            }

            return $this->redirectToRoute('user_index');
        }

        return $this->render('pages/user/index.html.twig', [
            'user' => $user,
            'userForm' => $formUser,
        ]);
    }
}
