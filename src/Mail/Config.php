<?php

declare(strict_types=1);

namespace Phenix\Mail;

use Phenix\Facades\Config as Configuration;
use Symfony\Component\Mailer\Transport\Dsn;
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

    public function dsn(): Dsn
    {
        $smtp = $this->config['mailers']['smtp'];

        $scheme = ! empty($smtp['encryption']) && $smtp['encryption'] === 'tls'
            ? (($smtp['port'] == 465) ? 'smtps' : 'smtp')
            : '';

        return new Dsn(
            $scheme,
            $smtp['host'],
            $smtp['username'] ?? null,
            $smtp['password'] ?? null,
            $smtp['port'] ?? null,
            $smtp
        );
    }

    public function from(): Address
    {
        return new Address($this->config['from']['address'], $this->config['from']['name'] ?? '');
    }

    public function setLogTransport(string $mailer): void
    {
        $this->config['mailers'][$mailer]['transport'] = 'log';
    }

    public function transport(string $mailer): string
    {
        return $this->config['mailers'][$mailer]['transport'] ?? 'smtp';
    }
}
