<?php

declare(strict_types=1);

namespace App\Tests\Services\User;

use App\Entity\User;
use App\Entity\UserInvitationCode;
use App\Services\User\InvitationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class InvitationServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private InvitationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->service = new InvitationService($this->em, new MockClock('2030-01-10 10:00:00'));
    }

    public function testUnusedAndNotExpiredCodeIsValid(): void
    {
        $code = (new UserInvitationCode())->setExpiredAt(new \DateTimeImmutable('2030-01-11 10:00:00'));

        $this->em->expects($this->never())->method('flush');

        $this->assertTrue($this->service->isInvitationCodeValid($code));
    }

    public function testExpiredCodeIsDeactivated(): void
    {
        $code = (new UserInvitationCode())->setExpiredAt(new \DateTimeImmutable('2030-01-09 10:00:00'));

        $this->em->expects($this->once())->method('flush');

        $this->assertFalse($this->service->isInvitationCodeValid($code));
        $this->assertFalse($code->isIsActivated());
    }

    public function testAlreadyUsedCodeIsDeactivated(): void
    {
        $code = (new UserInvitationCode())
            ->setExpiredAt(new \DateTimeImmutable('2030-01-11 10:00:00'))
            ->setUser(new User())
        ;

        $this->em->expects($this->once())->method('flush');

        $this->assertFalse($this->service->isInvitationCodeValid($code));
        $this->assertFalse($code->isIsActivated());
    }

    public function testRegisterPersistsTheUserAndDisablesTheCode(): void
    {
        $code = new UserInvitationCode();
        $user = new User();

        $this->em->expects($this->once())->method('persist')->with($user);
        $this->em->expects($this->once())->method('flush');

        $this->service->register($code, $user, 'my-password');

        $this->assertSame('my-password', $user->getPlainPassword());
        $this->assertSame($user, $code->getUser());
        $this->assertFalse($code->isIsActivated());
    }
}
