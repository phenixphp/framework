<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Amp\Future;
use Phenix\Tasks\WorkerPool;
use SensitiveParameter;
use Phenix\Tasks\Result;
use Phenix\Tasks\Worker;
use Phenix\Mail\Tasks\SendEmail;
use Phenix\Mail\Contracts\Mailable;
use Symfony\Component\Mime\Address;
use Phenix\Mail\Contracts\Mailer as MailerContract;

abstract class Mailer implements MailerContract
{
    protected array $to;

    protected array $cc;

    protected array $bcc;

    protected array $sendingLog;

    protected array $serviceConfig;

    public function __construct(
        protected Address $from,
        #[SensitiveParameter]
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

    public function send(Mailable $mailable): Future
    {
        $mailable->from($this->from)
            ->to($this->to)
            ->cc($this->cc)
            ->bcc($this->bcc)
            ->build();

        $email = $mailable->toMail();

        $execution = (new WorkerPool())->submitTask(
            new SendEmail(
                $email,
                $this->config,
                $this->serviceConfig,
            )
        );

        if ($this->config['transport'] === 'log') {
            $this->sendingLog[] = [
                'mailable' => $mailable::class,
                'email' => $email,
            ];
        }

        return $execution->getFuture();
    }

    public function getSendingLog(): array
    {
        return $this->sendingLog;
    }

    public function resetSendingLog(): void
    {
        $this->sendingLog = [];
    }

    protected function serviceConfig(): array
    {
        return [];
    }
}
