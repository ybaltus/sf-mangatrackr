<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use App\Form\UserPasswordType;
use App\Form\UserType;
use App\Security\Auth\AppAuthenticator;
use App\Services\Common\SpreadSheetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'user_index')]
    public function index(
        Request $request,
        Security $security,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator
    ): Response {
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
                $translator->trans('user.edit_user_success', domain: 'app')
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
                    $translator->trans('user.edit_pass_success', domain: 'app')
                );

                return $this->redirectToRoute('user_index');
            } else {
                $this->addFlash(
                    'warning',
                    $translator->trans('user.wrong_password', domain: 'app')
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

    #[Route('/delete-account', name: 'user_delete_account', methods: ['DELETE'])]
    public function deleteAccount(Security $security): JsonResponse
    {
        $user = $this->getUser();
        $statusCode = 200;
        $message = [
            'success',
            $this->generateUrl('home_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        try {
            // Remove UserInvitationCode
            $invitations = $this->em->getRepository(UserInvitationCode::class)->findByUser($user);
            if (count($invitations) > 0) {
                foreach ($invitations as $invit) {
                    $this->em->remove($invit);
                }
            }

            // Remove User (cascade with UserNews, UserTrackList and MangaUserTrack)
            $this->em->remove($user);

            // logout the user before the delete
            $response = $security->logout(false);

            $this->em->flush();
        } catch (\Exception $e) {
            $message[0] = $e->getMessage();
            $statusCode = 400;
        }

        return $this->json($message, $statusCode);
    }

    #[Route('/export/scantheque', name: 'user_export_scantheque', methods: ['GET'])]
    public function exportScantheque(SpreadSheetService $spreadSheetService): Response
    {
        $user = $this->getUser();
        /** @phpstan-ignore-next-line */
        $scanthequeDatasJson = json_decode($user->getScanthequeData(), true);
        $message = [
            'success',
            200,
        ];

        try {
            // Init parameters for the csv file
            $currentDate = (new \DateTimeImmutable())->format('Ymd');
            $filename = "mangasync-scantheque-$currentDate.csv";
            $columNames = ['Title', 'LastChapter', 'MaxChapter', 'Status'];
            $columValues = [];
            foreach ($scanthequeDatasJson as $mangasByStatus) {
                foreach ($mangasByStatus as $manga) {
                    $columValues[] = [
                        $manga['title'],
                        $manga['nbChaptersTrack'],
                        $manga['nbChapters'],
                        $manga['statusTrack'],
                    ];
                }
            }

            // Init SpreadSheet + Csv writer
            $spreadSheet = $spreadSheetService->createSimpleSpreadSheet($columNames, $columValues);
            $csvWriter = $spreadSheetService->createCsvWriter($spreadSheet);

            // Create StreamedResponse
            $response = new StreamedResponse();
            $contentType = 'text/csv';
            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename.'"');
            $response->headers->set('Cache-Control', 'max-age=0');
            $response->setPrivate();
            $response->setCallback(function () use ($csvWriter) {
                $csvWriter->save('php://output');
            });

            return $response;
        } catch (\Exception $e) {
            $message[0] = $e->getMessage();
            $statusCode = 400;
        }

        return $this->json($message, $statusCode);
    }
}
