<?php

declare(strict_types=1);

namespace App\Security\OAuth;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\Common\EmailNormalizer;
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
        private string $oauthTestEmails,
    ) {
        parent::__construct($registry, $router, $userRepository);
    }

    protected function getUserFromRessourceOwner(
        ResourceOwnerInterface $resourceOwner,
        UserRepository $userRepository,
    ): ?User {
        if (!$resourceOwner instanceof GoogleUser) {
            throw new \RuntimeException('Resource owner must be an instance of GoogleUser');
        }

        // Check if email is in test emails (**** Remove later ****)
        $allowedEmails = array_map(
            static fn (string $email): string => EmailNormalizer::normalize($email),
            explode(',', $this->oauthTestEmails)
        );
        if (!in_array(EmailNormalizer::normalize((string) $resourceOwner->getEmail()), $allowedEmails, true)) {
            throw new \RuntimeException('You are not allowed to use this application');
        }

        // Check if email is already verified
        if (false === ($resourceOwner->toArray()['email_verified'] ?? false)) {
            throw new \RuntimeException('Email must be verified');
        }

        $user = $userRepository->findOneByEmailInsensitive((string) $resourceOwner->getEmail());

        // Update idGoogle if user already exists but idGoogle is null
        if ($user && !$user->getIdGoogle()) {
            $user->setIdGoogle($resourceOwner->getId());
            $userRepository->flush();
        }

        return $user;
    }

    protected function persistNewUserFromRessourceOwner(
        ResourceOwnerInterface $resourceOwner,
        UserRepository $userRepository,
    ): User {
        /**
         * @var GoogleUser $resourceOwner
         */
        $user = new User();
        $user->setEmail((string) $resourceOwner->getEmail());
        $user->setPlainPassword('Google'.mt_rand(1, 999).'_'.time());
        $user->setUsername(explode('@', $resourceOwner->getEmail())[0]);
        $user->setIdGoogle($resourceOwner->getId());
        $userRepository->persistAndFlush($user, true);

        return $user;
    }
}
