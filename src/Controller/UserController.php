<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserPasswordType;
use App\Form\UserType;
use App\Security\Auth\AppAuthenticator;
use App\Services\Common\SpreadSheetService;
use App\Services\User\AccountService;
use App\Services\User\ScanthequeExportService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly LoggerInterface $logger,
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

        // Create the forms
        $formUser = $this->createForm(UserType::class, $user);
        $formUserPassword = $this->createForm(UserPasswordType::class);

        $formUser->handleRequest($request);
        $formUserPassword->handleRequest($request);

        // Handle formUser
        if ($formUser->isSubmitted() && $formUser->isValid()) {
            $data = $formUser->getData();

            $this->accountService->updateProfile();
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

            if ($this->accountService->changePassword($user, $data['plainPassword'], $data['newPassword'])) {
                $this->addFlash(
                    'success',
                    'Le mot de passe a été édité avec success !'
                );

                return $this->redirectToRoute('user_index');
            }
            $this->addFlash(
                'warning',
                'Le mot de passe renseigné est incorrect.'
            );

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
        /**
         * @var User $user
         */
        $user = $this->getUser();

        try {
            $this->accountService->deleteAccount($user);

            // logout the user once his account is deleted
            $security->logout(false);
        } catch (\Exception $e) {
            $this->logger->error('Unable to delete the user account.', ['exception' => $e]);

            return $this->json(['error', null], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success',
            $this->generateUrl('home_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    #[Route('/export/scantheque', name: 'user_export_scantheque', methods: ['GET'])]
    public function exportScantheque(
        SpreadSheetService $spreadSheetService,
        ScanthequeExportService $scanthequeExportService,
    ): Response {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        try {
            $rows = $scanthequeExportService->buildRows($user->getScanthequeData());

            // Init SpreadSheet + Csv writer
            $spreadSheet = $spreadSheetService->createSimpleSpreadSheet(ScanthequeExportService::COLUMN_NAMES, $rows);
            $csvWriter = $spreadSheetService->createCsvWriter($spreadSheet);
        } catch (\Exception $e) {
            $this->logger->error('Unable to export the scantheque.', ['exception' => $e]);

            return $this->json(['error', Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        }

        // Create StreamedResponse
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set(
            'Content-Disposition',
            'attachment;filename="'.$scanthequeExportService->getFilename().'"'
        );
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->setPrivate();
        $response->setCallback(function () use ($csvWriter) {
            $csvWriter->save('php://output');
        });

        return $response;
    }
}
