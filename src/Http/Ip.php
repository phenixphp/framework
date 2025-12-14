<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\Server\Middleware\Forwarded;
use Amp\Http\Server\Request;

class Ip
{
    protected string $address;

    protected string $host;

    protected int|null $port = null;

    protected string|null $forwardingAddress = null;

    public function __construct(Request $request)
    {
        $this->address = $request->getClient()->getRemoteAddress()->toString();

        if ($request->hasAttribute(Forwarded::class)) {
            /** @var Forwarded|null $forwarded */
            $forwarded = $request->getAttribute(Forwarded::class);

            $this->forwardingAddress = $forwarded->getFor()->toString();
        }
    }

    public static function make(Request $request): self
    {
        $ip = new self($request);
        $ip->parse();

        return $ip;
    }

    public function address(): string
    {
        return $this->address;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int|null
    {
        return $this->port;
    }

    public function isForwarded(): bool
    {
        return ! empty($this->forwardingAddress);
    }

    public function forwardingAddress(): string|null
    {
        return $this->forwardingAddress;
    }

    public function hash(): string
    {
        return hash('sha256', $this->host);
    }

    protected function parse(): void
    {
        $address = trim($this->address);

        if (preg_match('/^\[(?<addr>[^\]]+)\](?::(?<port>\d+))?$/', $address, $m) === 1) {
            $this->host = $m['addr'];
            $this->port = isset($m['port']) ? (int) $m['port'] : null;

            return;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->host = $address;
            $this->port = null;

            return;
        }

        if (str_contains($address, ':')) {
            [$maybeHost, $maybePort] = explode(':', $address, 2);

            if (
                filter_var($maybeHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
                filter_var($maybeHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ) {
                $this->host = $maybeHost;
                $this->port = is_numeric($maybePort) ? (int) $maybePort : null;

                return;
            }
        }

        $this->host = $address;
        $this->port = null;
    }
}
