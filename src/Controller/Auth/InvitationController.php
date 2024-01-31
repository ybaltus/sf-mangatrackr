<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use App\Form\Auth\InvitationFormType;
use App\Security\Auth\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/invitation/register/{codeInvitation}', name: 'invitation_register')]
    public function register(
        UserInvitationCode $userInvitationCode,
        Request $request,
        Security $security,
    ): Response {
        // logout the user in on the current firewall and disabled the csrf logout
        if ($this->getUser()) {
            $security->logout(false);
        }

        // Verify the invitation code
        if (!$this->isInvitationCodeValid($userInvitationCode)) {
            throw new \Exception('This invitation code is no longer valid.');
        }

        $user = new User();
        $form = $this->createForm(InvitationFormType::class, $user, [
            'emailInvitation' => $userInvitationCode->getEmail(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify email is matching
            if ($userInvitationCode->getEmail() !== $user->getEmail()) {
                $this->addFlash(
                    'warning',
                    'The email does not match this invitation code.'
                );
            } else {
                // Use setPlainPassword to set the plainPassword for prePersist doctrineEvent(UserEntityListener)
                $user->setPlainPassword($form->get('plainPassword')->getData());

                // Disable the invitation code
                $userInvitationCode->setUser($user);
                $userInvitationCode->setIsActivated(false);

                $this->em->persist($user);
                $this->em->flush();

                // Login the user
                $security->login($user, AppAuthenticator::class);

                return $this->redirectToRoute('scantheque_index');
            }
        }

        return $this->render('pages/auth/invitation/register.html.twig', [
            'invitationForm' => $form->createView(),
            'userEmail' => $userInvitationCode->getEmail(),
        ]);
    }

    private function isInvitationCodeValid(UserInvitationCode $userInvitationCode): bool
    {
        $isValid = false;
        $dateNow = new \DateTimeImmutable();

        // Expired or already used
        if (
            !$userInvitationCode->getUser()
            && ($dateNow < $userInvitationCode->getExpiredAt())
        ) {
            $isValid = true;
        }

        // Disable if not valid
        if (!$isValid) {
            $userInvitationCode->setIsActivated(false);
            $this->em->flush();
        }

        return $isValid;
    }
}
