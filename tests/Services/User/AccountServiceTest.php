<?php

declare(strict_types=1);

namespace App\Tests\Services\User;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use App\Repository\UserInvitationCodeRepository;
use App\Services\User\AccountService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private AccountService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->service = new AccountService($this->em, $this->passwordHasher, new MockClock('2030-01-10 10:00:00'));
    }

    public function testChangePasswordWithTheValidCurrentPassword(): void
    {
        $user = new User();

        $this->passwordHasher->method('isPasswordValid')->with($user, 'current')->willReturn(true);
        $this->em->expects($this->once())->method('flush');

        $this->assertTrue($this->service->changePassword($user, 'current', 'new-password'));
        $this->assertSame('new-password', $user->getPlainPassword());
        $this->assertEquals(new \DateTimeImmutable('2030-01-10 10:00:00'), $user->getUpdatedAt());
    }

    public function testChangePasswordWithAnInvalidCurrentPassword(): void
    {
        $user = new User();

        $this->passwordHasher->method('isPasswordValid')->willReturn(false);
        $this->em->expects($this->never())->method('flush');

        $this->assertFalse($this->service->changePassword($user, 'wrong', 'new-password'));
        $this->assertNotSame('new-password', $user->getPlainPassword());
        $this->assertNull($user->getUpdatedAt());
    }

    public function testDeleteAccountRemovesTheInvitationsAndTheUser(): void
    {
        $user = new User();
        $invitation = new UserInvitationCode();

        $repository = $this->getMockBuilder(UserInvitationCodeRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findByUser'])
            ->getMock();
        $repository->expects($this->once())->method('findByUser')->with($user)->willReturn([$invitation]);

        $this->em->method('getRepository')->with(UserInvitationCode::class)->willReturn($repository);
        $removed = [];
        $this->em->expects($this->exactly(2))->method('remove')
            ->willReturnCallback(static function (object $entity) use (&$removed): void {
                $removed[] = $entity;
            });
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteAccount($user);

        $this->assertSame([$invitation, $user], $removed);
    }
}
