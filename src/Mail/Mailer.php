<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Closure;
use Phenix\Mail\Contracts\Mailable;
use Phenix\Mail\Contracts\Mailer as MailerContract;
use Phenix\Mail\Transports\LogTransport;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Throwable;

class Mailer implements MailerContract
{
    protected array $to;

    protected array $cc;

    protected array $bcc;

    protected array $sendingLog;

    public function __construct(
        protected TransportInterface $transport,
        protected Config $config
    ) {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->sendingLog = [];
    }

    public function to(array|string $to): self
    {
        $this->to = (array) $to;

        return $this;
    }

    public function cc(array|string $cc): self
    {
        $this->cc = (array) $cc;

        return $this;
    }

    public function bcc(array|string $bcc): self
    {
        $this->bcc = (array) $bcc;

        return $this;
    }

    public function send(Mailable $mailable, array $data = [], Closure|null $callback = null): void
    {
        $mailer = new SymfonyMailer($this->transport);

        $mailable->from($this->config->from())
            ->to($this->to)
            ->cc($this->cc)
            ->bcc($this->bcc)
            ->build();

        $email = $mailable->toMail();

        if ($this->transport instanceof LogTransport) {
            $this->sendingLog[$mailable::class] = [
                'body' => $email->getHtmlBody(),
                'subject' => $email->getSubject(),
                'from' => $email->getFrom(),
                'to' => $email->getTo(),
                'cc' => $email->getCc(),
                'bcc' => $email->getBcc(),
                'replyTo' => $email->getReplyTo(),
                'success' => true,
            ];
        }

        try {
            $mailer->send($email);
        } catch (Throwable) {
            if ($this->transport instanceof LogTransport) {
                $this->sendingLog[$mailable::class]['success'] = false;
            }

            return;
        }
    }

    public function getSendingLog(): array
    {
        return $this->sendingLog;
    }
}
