<?php

namespace App\Security\OAuth;

use App\Entity\User;
use App\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\Routing\RouterInterface;

final class GoogleAuthenticator extends AbstractOAuthAuthenticator
{
    protected string $providerName = 'google';

    public function __construct(
        ClientRegistry $registry,
        RouterInterface $router,
        UserRepository $userRepository,
        private string $oauthTestEmails
    ) {
        parent::__construct($registry, $router, $userRepository);
    }

    protected function getUserFromRessourceOwner(
        ResourceOwnerInterface $resourceOwner,
        UserRepository $userRepository
    ): ?User {
        if (!($resourceOwner instanceof GoogleUser)) {
            throw new \RuntimeException('Resource owner must be an instance of GoogleUser');
        }

        // Check if email is in test emails (**** Remove later ****)
        if (!in_array($resourceOwner->getEmail(), explode(',', $this->oauthTestEmails))) {
            throw new \RuntimeException('You are not allowed to use this application');
        }

        // Check if email is already verified
        if (false === ($resourceOwner->toArray()['email_verified'] ?? false)) {
            throw new \RuntimeException('Email must be verified');
        }

        $user = $userRepository->findOneBy([
            'email' => $resourceOwner->getEmail(),
        ]);

        // Update idGoogle if user already exists but idGoogle is null
        if ($user && !$user->getIdGoogle()) {
            $user->setIdGoogle($resourceOwner->getId());
            $userRepository->flush();
        }

        return $user;
    }

    protected function persistNewUserFromRessourceOwner(
        ResourceOwnerInterface $resourceOwner,
        UserRepository $userRepository
    ): User {
        /**
         * @var GoogleUser $resourceOwner
         */
        $user = new User();
        $user->setEmail($resourceOwner->getEmail());
        $user->setPlainPassword('Google'.mt_rand(1, 999).'_'.time());
        $user->setUsername(explode('@', $resourceOwner->getEmail())[0]);
        $user->setIdGoogle($resourceOwner->getId());
        $userRepository->persistAndFlush($user, true);

        return $user;
    }
}
