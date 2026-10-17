<?php

declare(strict_types=1);

namespace App\Tests\Services\User;

use App\Entity\User;
use App\Entity\UserResetPassword;
use App\Services\Common\MailService;
use App\Services\User\ResetPasswordService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResetPasswordServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UrlGeneratorInterface&MockObject $router;
    private MailService&MockObject $mailService;
    private MockClock $clock;
    private ResetPasswordService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->clock = new MockClock('2030-01-10 10:00:00');

        $this->service = new ResetPasswordService(
            $this->em,
            $this->passwordHasher,
            $this->router,
            $this->mailService,
            $this->clock,
            'MangaTrackr',
            'no-reply@mangatrackr.test',
        );
    }

    public function testRequestResetCreatesACodeAndSendsTheEmail(): void
    {
        $user = (new User())->setEmail('user@mangatrackr.test');

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(UserResetPassword::class));
        $this->em->expects($this->exactly(2))->method('flush');
        $this->router->expects($this->once())->method('generate')
            ->with('reset_password_update', $this->arrayHasKey('resetCode'), UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://mangatrackr.test/reset_password/edit_password/code');
        $this->mailService->expects($this->once())->method('sendEmail')
            ->with(
                $this->equalTo(new Address('no-reply@mangatrackr.test', 'MangaTrackr')),
                'user@mangatrackr.test',
                'MangaTrackr - Modification de mot de passe',
                'emails/reset_password.html.twig',
                $this->arrayHasKey('resetUrl')
            )
            ->willReturn('Email sent successfully.');

        $userResetPassword = $this->service->requestReset($user);

        $this->assertSame($user, $userResetPassword->getUser());
        $this->assertNotEmpty($userResetPassword->getResetCode());
        $this->assertSame('Email sent successfully.', $userResetPassword->getSendingEmailStatus());
    }

    public function testRequestResetRenewsTheExistingCode(): void
    {
        $user = (new User())->setEmail('user@mangatrackr.test');
        $existing = (new UserResetPassword())
            ->setResetCode('old-code')
            ->setIsActivated(false)
            ->setSendingEmailStatus('old status')
        ;
        $user->addUserResetPassword($existing);

        $this->em->expects($this->never())->method('persist');
        $this->mailService->method('sendEmail')->willReturn('Email sent successfully.');

        $userResetPassword = $this->service->requestReset($user);

        $this->assertSame($existing, $userResetPassword);
        $this->assertNotSame('old-code', $userResetPassword->getResetCode());
        $this->assertTrue($userResetPassword->isIsActivated());
        $this->assertEquals(new \DateTimeImmutable('2030-01-10 10:00:00'), $userResetPassword->getCreatedAt());
        $this->assertEquals(new \DateTimeImmutable('2030-01-11 10:00:00'), $userResetPassword->getExpiredAt());
    }

    public function testResetCodeIsValidBeforeExpiration(): void
    {
        $userResetPassword = (new UserResetPassword())
            ->setExpiredAt(new \DateTimeImmutable('2030-01-11 10:00:00'))
        ;

        $this->em->expects($this->never())->method('flush');

        $this->assertTrue($this->service->isResetCodeValid($userResetPassword));
        $this->assertTrue($userResetPassword->isIsActivated());
    }

    public function testExpiredResetCodeIsDeactivated(): void
    {
        $userResetPassword = (new UserResetPassword())
            ->setExpiredAt(new \DateTimeImmutable('2030-01-10 09:59:59'))
        ;

        $this->em->expects($this->once())->method('flush');

        $this->assertFalse($this->service->isResetCodeValid($userResetPassword));
        $this->assertFalse($userResetPassword->isIsActivated());
    }

    public function testAlreadyUsedResetCodeIsInvalid(): void
    {
        $userResetPassword = (new UserResetPassword())
            ->setExpiredAt(new \DateTimeImmutable('2030-01-11 10:00:00'))
            ->setIsActivated(false)
        ;

        $this->assertFalse($this->service->isResetCodeValid($userResetPassword));
    }

    public function testUpdatePasswordHashesThePasswordAndDisablesTheCode(): void
    {
        $user = (new User())->setPlainPassword('new-password');
        $userResetPassword = (new UserResetPassword())->setUser($user);

        $this->passwordHasher->expects($this->once())->method('hashPassword')
            ->with($user, 'new-password')
            ->willReturn('hashed-password');
        $this->em->expects($this->once())->method('flush');

        $this->service->updatePassword($userResetPassword);

        $this->assertSame('hashed-password', $user->getPassword());
        $this->assertFalse($userResetPassword->isIsActivated());
    }
}
