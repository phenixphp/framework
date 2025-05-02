<?php

declare(strict_types=1);

namespace Phenix\Mail\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Facades\Log;
use Phenix\Mail\TransportFactory;
use Phenix\Tasks\ParallelTask;
use Phenix\Tasks\Result;
use SensitiveParameter;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Throwable;

class SendEmail extends ParallelTask
{
    public function __construct(
        #[SensitiveParameter]
        private Email $email,
        #[SensitiveParameter]
        private array $mailerConfig,
        #[SensitiveParameter]
        private array $serviceConfig = [],
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): Result
    {
        try {
            $transport = TransportFactory::make(
                $this->mailerConfig,
                $this->serviceConfig
            );

            $mailer = new Mailer($transport);
            $mailer->send($this->email);

            return Result::success();
        } catch (Throwable $e) {
            Log::error(
                'Failed to send email',
                [
                    'exception' => $e,
                    'email' => $this->email->toString(),
                ]
            );

            return Result::failure();
        }
    }
}
