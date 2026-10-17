<?php

declare(strict_types=1);

namespace App\Tests\Services\Common;

use App\Services\Common\MailService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class MailServiceTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private LoggerInterface&MockObject $logger;
    private MailService $service;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new MailService($this->mailer, $this->logger);
    }

    public function testSendEmailSuccess(): void
    {
        $this->mailer->expects($this->once())->method('send')->with($this->callback(
            static function (TemplatedEmail $email): bool {
                return 'Subject' === $email->getSubject()
                    && 'emails/template.html.twig' === $email->getHtmlTemplate()
                    && ['name' => 'value'] === $email->getContext()
                    && 'to@mangatrackr.test' === $email->getTo()[0]->getAddress()
                    && 'from@mangatrackr.test' === $email->getFrom()[0]->getAddress();
            }
        ));
        $this->logger->expects($this->never())->method('error');

        $result = $this->service->sendEmail(
            new Address('from@mangatrackr.test', 'MangaTrackr'),
            'to@mangatrackr.test',
            'Subject',
            'emails/template.html.twig',
            ['name' => 'value']
        );

        $this->assertSame('Email sent successfully.', $result);
    }

    public function testSendEmailFailureIsLoggedAndReturnsTheStatus(): void
    {
        $this->mailer->method('send')->willThrowException(new TransportException('SMTP down'));
        $this->logger->expects($this->once())->method('error')
            ->with('Unable to send the email.', $this->arrayHasKey('exception'));

        $result = $this->service->sendEmail('from@mangatrackr.test', 'to@mangatrackr.test', 'Subject', 'tpl.html.twig', []);

        $this->assertSame('Email not sent : SMTP down', $result);
    }
}
