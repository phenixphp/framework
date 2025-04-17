<?php

declare(strict_types=1);

namespace Phenix\Mail\Tasks;

use Amp\Cancellation;
use Amp\Sync\Channel;
use Phenix\Facades\Log;
use Phenix\Tasks\ParallelTask;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Throwable;

class SendEmail extends ParallelTask
{
    public function __construct(
        private Mailer $mailer,
        private Email $email,
    ) {
        parent::__construct();
    }

    protected function handle(Channel $channel, Cancellation $cancellation): bool
    {
        try {
            $this->mailer->send($this->email);

            return true;
        } catch (Throwable $e) {
            Log::error(
                'Failed to send email',
                [
                    'exception' => $e,
                    'email' => $this->email->toString(),
                ]
            );

            return false;
        }
    }
}
