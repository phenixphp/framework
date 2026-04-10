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

        if ($request->hasAttribute(Forwarded::class) && $forwarded = $request->getAttribute(Forwarded::class)) {
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
        [$host] = $this->parseAddress($this->forwardingAddress ?? $this->host);

        return hash('sha256', $host);
    }

    protected function parse(): void
    {
        [$this->host, $this->port] = $this->parseAddress($this->address);
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    protected function parseAddress(string $address): array
    {
        $address = trim($address);

        if (preg_match('/^\[(?<addr>[^\]]+)\](?::(?<port>\d+))?$/', $address, $m) === 1) {
            return [$m['addr'], isset($m['port']) ? (int) $m['port'] : null];
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return [$address, null];
        }

        if (str_contains($address, ':')) {
            [$maybeHost, $maybePort] = explode(':', $address, 2);

            if (
                filter_var($maybeHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ||
                filter_var($maybeHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ) {
                return [$maybeHost, is_numeric($maybePort) ? (int) $maybePort : null];
            }
        }

        return [$address, null];
    }
}
