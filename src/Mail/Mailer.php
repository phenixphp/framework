<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Closure;
use Phenix\Mail\Contracts\Mailable;
use Phenix\Mail\Contracts\Mailer as MailerContract;
use Phenix\Mail\Tasks\SendEmail;
use Phenix\Tasks\TaskPool;
use Symfony\Component\Mime\Address;

abstract class Mailer implements MailerContract
{
    protected array $to;

    protected array $cc;

    protected array $bcc;

    protected array $sendingLog;

    protected array $serviceConfig;

    public function __construct(
        protected Address $from,
        protected array $config
    ) {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->sendingLog = [];
        $this->serviceConfig = $this->serviceConfig();
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
        $mailable->from($this->from)
            ->to($this->to)
            ->cc($this->cc)
            ->bcc($this->bcc)
            ->build();

        $email = $mailable->toMail();

        [$result] = TaskPool::pool([
            new SendEmail(
                $email,
                $this->config,
                $this->serviceConfig,
            ),
        ]);

        if ($this->config['transport'] === 'log') {
            $this->sendingLog[] = [
                'mailable' => $mailable::class,
                'email' => $email,
                'success' => $result,
            ];
        }
    }

    public function getSendingLog(): array
    {
        return $this->sendingLog;
    }

    protected function serviceConfig(): array
    {
        return [];
    }
}
