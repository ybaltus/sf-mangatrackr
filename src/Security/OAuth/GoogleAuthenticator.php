<?php

namespace App\Security\OAuth;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final class GoogleAuthenticator extends AbstractOAuthAuthenticator
{
    protected string $providerName = 'google';

    protected function getUserFromRessourceOwner(
        ResourceOwnerInterface $resourceOwner,
        UserRepository $userRepository
    ): ?User {
        if (!($resourceOwner instanceof GoogleUser)) {
            throw new \LogicException('Resource owner must be an instance of GoogleUser');
        }

        // Check if email is already verified
        if (false === ($resourceOwner->toArray()['email_verified'] ?? false)) {
            throw new \LogicException('Email must be verified');
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
