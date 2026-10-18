<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserResetPassword;
use App\Form\ResetPasswordEmailType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Services\User\ResetPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reset_password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly ResetPasswordService $resetPasswordService,
    ) {
    }

    #[Route('', name: 'reset_password_index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        // Redirect user already connected
        if ($this->getUser()) {
            return $this->redirectToRoute('user_index');
        }

        $resetPasswordForm = $this->createForm(ResetPasswordEmailType::class);
        $resetPasswordForm->handleRequest($request);

        if ($resetPasswordForm->isSubmitted() && $resetPasswordForm->isValid()) {
            $data = $resetPasswordForm->getData();

            // Check if email exist
            $user = $userRepository->findOneByEmailInsensitive((string) $data['email']);
            if ($user) {
                $this->resetPasswordService->requestReset($user);

                $this->addFlash(
                    'success',
                    "Un email de modification vient d'être envoyé."
                );

                return $this->redirectToRoute('reset_password_index');
            }
            $this->addFlash(
                'warning',
                "Cette email n'existe pas ou est incorrect."
            );
        }

        return $this->render('pages/reset_password/index.html.twig', [
            'resetPasswordForm' => $resetPasswordForm->createView(),
        ]);
    }

    #[Route('/edit_password/{resetCode}', name: 'reset_password_update')]
    public function updatePassword(
        Request $request,
        UserResetPassword $userResetPassword,
    ): Response {
        // Verify the reset code
        if (!$this->resetPasswordService->isResetCodeValid($userResetPassword)) {
            throw new GoneHttpException('This reset code is no longer valid.');
        }

        $passwordForm = $this->createForm(ResetPasswordType::class, $userResetPassword->getUser());
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $this->resetPasswordService->updatePassword($userResetPassword);

            $this->addFlash(
                'success',
                'Votre mot de passe a été modifié avec succès! '
            );

            return $this->redirectToRoute('security_login');
        }

        return $this->render('pages/reset_password/edit_password.html.twig', [
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}
