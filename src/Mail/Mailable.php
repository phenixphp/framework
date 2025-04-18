<?php

declare(strict_types=1);

namespace Phenix\Mail;

use InvalidArgumentException;
use Phenix\Facades\File;
use Phenix\Facades\View;
use Phenix\Mail\Contracts\Mailable as MailableContract;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File as MailFile;

use function is_array;
use function is_string;

abstract class Mailable implements MailableContract
{
    public array $from = [];

    public array $to = [];

    public array $cc = [];

    public array $bcc = [];

    public array $replyTo = [];

    public string $subject;

    public string $html;

    public string $view;

    public string $textView;

    public array $viewData = [];

    public array $attachments = [];

    public array $metadata = [];

    abstract public function build(): self;

    public function to(array|string $to): self
    {
        $this->to = array_merge($this->to, (array) $to);

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

    public function toMail(): Email
    {
        $email = new Email();

        $this->html = View::view($this->view, $this->viewData)->render();

        $email->from(...$this->from)
            ->to(...$this->to)
            ->cc(...$this->cc)
            ->bcc(...$this->bcc)
            ->replyTo(...$this->replyTo)
            ->subject($this->subject)
            ->html($this->html);

        foreach ($this->attachments as $attachment) {
            $file = new MailFile($attachment['path'], $attachment['name']);

            $email->addPart(new DataPart(
                $file,
                $attachment['name'],
                $attachment['mime']
            ));
        }

        $this->reset();

        return $email;
    }

    public function from(Address|array|string $from): self
    {
        $this->from = $from instanceof Address ? [$from] : (array) $from;

        return $this;
    }

    protected function replyTo(array|string $replyTo): self
    {
        $this->replyTo = (array) $replyTo;

        return $this;
    }

    protected function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    protected function view(string $view, array $viewData = []): self
    {
        $this->view = $view;
        $this->viewData = $viewData;

        return $this;
    }

    protected function attachment(string $path, string|null $name = null, string|null $mime = null): self
    {
        if (File::exists($path) === false) {
            throw new InvalidArgumentException("File {$path} does not exist.");
        }

        $this->attachments[] = [
            'path' => $path,
            'name' => $name,
            'mime' => $mime,
        ];

        return $this;
    }

    protected function attachments(array $attachments): self
    {
        foreach ($attachments as $attachment) {
            if (is_string($attachment)) {
                $this->attachment($attachment);
            } elseif (is_array($attachment)) {
                $this->attachment(
                    $attachment['path'],
                    $attachment['name'] ?? null,
                    $attachment['mime'] ?? null
                );
            }
        }

        return $this;
    }

    protected function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    private function reset(): void
    {
        $this->from = [];
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->replyTo = [];
        $this->subject = '';
        $this->html = '';
        $this->view = '';
        $this->textView = '';
        $this->viewData = [];
        $this->attachments = [];
        $this->metadata = [];
    }
}
