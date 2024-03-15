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

        /**
         * @var GoogleUser $resourceOwner
         */
        return $userRepository->findOneBy([
            'idGoogle' => $resourceOwner->getId(),
            'email' => $resourceOwner->getEmail(),
        ]);
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
