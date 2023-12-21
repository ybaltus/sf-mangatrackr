<?php

namespace App\Services\Common;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    /**
     * @param array<mixed> $context
     */
    public function sendEmail(
        string|Address $from,
        string|Address $to,
        string $subject,
        string $htmlTemplate,
        array $context,
    ): void {
        $email = (new TemplatedEmail())
            ->from($from)
            ->to($to)
            ->subject($subject)
            // path of the Twig template to render
            ->htmlTemplate($htmlTemplate)
            // pass variables (name => value) to the template
            ->context($context);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
            var_dump($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
