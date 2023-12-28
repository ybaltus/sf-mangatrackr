<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserResetPassword;
use App\Form\ResetPasswordEmailType;
use App\Form\ResetPasswordType;
use App\Services\Common\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/reset_password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $router,
        private readonly MailService $mailService,
    ) {
    }

    #[Route('', name: 'reset_password_index')]
    public function index(Request $request): Response
    {
        $resetPasswordForm = $this->createForm(ResetPasswordEmailType::class);
        $resetPasswordForm->handleRequest($request);

        if ($resetPasswordForm->isSubmitted() && $resetPasswordForm->isValid()) {
            $data = $resetPasswordForm->getData();

            // Check if email exist
            /**
             * @var User|null $user
             */
            $user = $this->em->getRepository(User::class)->findOneByEmail($data['email']);
            if ($user) {
                $userResetPassword = null;
                // Check if an resetCode already exists
                if (0 === count($user->getUserResetPasswords())) {
                    $userResetPassword = (new UserResetPassword())
                        ->setUser($user)
                        ->setResetCode(Uuid::v4())
                    ;
                    $this->em->persist($userResetPassword);
                } else {
                    $createdAt = new \DateTimeImmutable();
                    $expiredAt = $createdAt->add(new \DateInterval('P1D'));
                    $user->getUserResetPasswords()[0]
                        ->setResetCode(Uuid::v4())
                        ->setCreatedAt($createdAt)
                        ->setExpiredAt($expiredAt)
                        ->setSendingEmailStatus('')
                        ->setIsActivated(true)
                    ;
                    $userResetPassword = $user->getUserResetPasswords()[0];
                }
                // Flush in db
                $this->em->flush();

                // Send Email
                $this->sendEmail($userResetPassword, 'emails/reset_password.html.twig', 'Modification de mot de passe');

                // Flash message
                $this->addFlash(
                    'success',
                    "Un email de modification vient d'être envoyé."
                );

                return $this->redirectToRoute('reset_password_index');
            } else {
                $this->addFlash(
                    'warning',
                    "Cette email n'existe pas ou est incorrect."
                );
            }
        }

        return $this->render('pages/reset_password/index.html.twig', [
            'resetPasswordForm' => $resetPasswordForm->createView(),
        ]);
    }

    #[Route('/edit_password/{resetCode}', name: 'reset_password_update')]
    public function updatePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserResetPassword $userResetPassword
    ): Response {
        // Verify the invitation code
        if (!$this->isResetCodeValid($userResetPassword)) {
            throw new \Exception('This reset code is no longer valid.');
        }

        $passwordForm = $this->createForm(ResetPasswordType::class, $userResetPassword->getUser());
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $user = $passwordForm->getData();

            // Hash password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            );

            $user->setPassword($hashedPassword);

            // Disable the reset code
            $userResetPassword->setIsActivated(false);

            $this->em->flush();

            // Flash message
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

    private function sendEmail(UserResetPassword $userResetPassword, string $htmlTemplate, string $subject): void
    {
        // Params
        $appName = $this->getParameter('app_name');
        $emailFrom = $this->getParameter('mailer_no_reply');
        $resetCode = $userResetPassword->getResetCode();
        $emailTo = $userResetPassword->getUser()->getEmail();
        $expiredAt = $userResetPassword->getExpiredAt();
        $context = [
            'emailTo' => $emailTo,
            'resetUrl' => $this->router->generate('reset_password_update', [
                'resetCode' => $resetCode,
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'expiredAt' => $expiredAt,
        ];

        // Send email
        $result = $this->mailService->sendEmail(
            new Address($emailFrom, $appName),
            $emailTo,
            sprintf('%s - %s', $appName, $subject),
            $htmlTemplate,
            $context
        );

        // Save the result of email
        $userResetPassword->setSendingEmailStatus($result);
        $this->em->flush();
    }

    private function isResetCodeValid(UserResetPassword $userResetPassword): bool
    {
        $isValid = false;
        $dateNow = new \DateTimeImmutable();

        // Expired or already used
        if (
            $userResetPassword->isIsActivated()
            && ($dateNow < $userResetPassword->getExpiredAt())
        ) {
            $isValid = true;
        }

        // Disable if not valid
        if (!$isValid) {
            $userResetPassword->setIsActivated(false);
            $this->em->flush();
        }

        return $isValid;
    }
}
