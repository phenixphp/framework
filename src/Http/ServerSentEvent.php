<?php

declare(strict_types=1);

namespace Phenix\Http;

use Stringable;

class ServerSentEvent implements Stringable
{
    public function __construct(
        public readonly string $data,
        public readonly string|null $event = null,
        public readonly string|null $id = null,
        public readonly int|null $retry = null,
        public readonly string|null $comment = null
    ) {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        $lines = [];

        if ($this->comment !== null) {
            foreach ($this->lines($this->comment) as $line) {
                $lines[] = ": {$line}";
            }
        }

        if ($this->event !== null) {
            $lines[] = "event: {$this->event}";
        }

        if ($this->id !== null) {
            $lines[] = "id: {$this->id}";
        }

        if ($this->retry !== null) {
            $lines[] = "retry: {$this->retry}";
        }

        foreach ($this->lines($this->data) as $line) {
            $lines[] = "data: {$line}";
        }

        return implode("\n", $lines) . "\n\n";
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $value): array
    {
        return explode("\n", str_replace(["\r\n", "\r"], "\n", $value));
    }
}
