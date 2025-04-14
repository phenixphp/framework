<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Phenix\Facades\Config as Configuration;
use Phenix\Mail\Constants\MailerType;
use Symfony\Component\Mime\Address;

class Config
{
    private array $config;

    public function __construct()
    {
        $this->config = Configuration::get('mail', []);
    }

    public function default(): string
    {
        return $this->config['default'] ?? 'smtp';
    }

    public function from(): Address
    {
        return new Address($this->config['from']['address'], $this->config['from']['name'] ?? '');
    }

    public function setLogTransport(string $mailer): void
    {
        $this->config['mailers'][$mailer]['transport'] = 'log';
    }

    public function get(MailerType $mailer): array
    {
        return $this->config['mailers'][$mailer->value];
    }
}
