<?php

namespace App\Controller\Auth;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public const OAUTH_PROVIDER_SCOPES = [
        'google' => [],
    ];

    #[Route(path: '/login', name: 'security_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render(
            'pages/auth/security/login.html.twig',
            [
                'last_username' => $lastUsername,
                'error' => $error,
            ]
        );
    }

    #[Route(path: '/logout', name: 'security_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank -
         it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/oauth/connect/{provider}', name: 'security_oauth_connect')]
    public function oAuthConnect(string $provider, ClientRegistry $clientRegistry): RedirectResponse
    {
        if (!in_array($provider, array_keys(self::OAUTH_PROVIDER_SCOPES), true)) {
            throw $this->createNotFoundException();
        }

        return $clientRegistry->getClient($provider)->redirect(self::OAUTH_PROVIDER_SCOPES[$provider], []);
    }

    #[Route('/oauth/callback/{provider}', name: 'security_oauth_callback')]
    public function oAuthCallback(): Response
    {
        return new Response(status: 200);
    }
}
