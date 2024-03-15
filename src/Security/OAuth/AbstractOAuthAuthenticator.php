<?php

namespace App\Security\OAuth;

use App\Entity\User;
use App\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

abstract class AbstractOAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    protected const LOGIN_ROUTE = 'security_login';
    protected const SCANTHEQUE_ROUTE = 'scantheque_index';
    protected string $providerName = '';

    public function __construct(
        private readonly ClientRegistry $registry,
        private readonly RouterInterface $router,
        private readonly UserRepository $userRepository
    ) {
    }

    abstract protected function getUserFromRessourceOwner(
        ResourceOwnerInterface $resourceOwner,
        UserRepository $userRepository
    ): ?User;

    abstract protected function persistNewUserFromRessourceOwner(
        ResourceOwnerInterface $resourceOwner,
        UserRepository $userRepository
    ): User;

    public function supports(Request $request): ?bool
    {
        return 'security_oauth_callback' === $request->attributes->get('_route')
            && $this->providerName === $request->get('provider');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->getClient();
        $accessToken = $this->fetchAccessToken($client);
        $resourceOwner = $this->getRessourceOwnerFromAccessToken($accessToken);
        $user = $this->getUserFromRessourceOwner($resourceOwner, $this->userRepository);

        if (null === $user) {
            $user = $this->persistNewUserFromRessourceOwner($resourceOwner, $this->userRepository);
        }

        return new SelfValidatingPassport(
            userBadge: new UserBadge(
                $user->getUserIdentifier(),
                fn () => $user
            ),
            badges: [
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect back
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        /**
         * Save current date for lastConnectedAt field of user.
         *
         * @var User $user
         */
        $user = $token->getUser();
        $user->setLastConnectedAt(new \DateTimeImmutable());
        $this->userRepository->flush();

        // Redirect to scantheque
        return new RedirectResponse($this->router->generate(self::SCANTHEQUE_ROUTE));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        // Redirect to login route
        return new RedirectResponse($this->router->generate(self::LOGIN_ROUTE));
    }

    public function start(Request $request, ?AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            $this->router->generate(self::LOGIN_ROUTE), // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    protected function getRessourceOwnerFromAccessToken(AccessToken $accessToken): ResourceOwnerInterface
    {
        return $this->getClient()->fetchUserFromToken($accessToken);
    }

    private function getClient(): OAuth2ClientInterface
    {
        return $this->registry->getClient($this->providerName);
    }
}
