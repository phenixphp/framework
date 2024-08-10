<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\FormParser\StreamedField;
use Amp\Http\Server\FormParser\StreamingFormParser;
use Amp\Http\Server\Request;
use Amp\Pipeline\ConcurrentIterator;
use Amp\Pipeline\Internal\ConcurrentQueueIterator;

class StreamParser extends BodyParser
{
    protected StreamingFormParser $parser;

    /**
     * @var ConcurrentQueueIterator<int, StreamedField>
     */
    protected ConcurrentQueueIterator $body;
    protected array $files;
    protected array $data;

    public function __construct(
        protected int|null $bodySizeLimit = null,
        protected int|null $fieldCountLimit = null
    ) {
        $this->parser = new StreamingFormParser($fieldCountLimit);
        $this->files = [];
        $this->data = [];
    }

    public static function fromRequest(Request $request, array $options = []): self
    {
        $bodySizeLimit = $options['body_size_limit'] ?? 120 * 1024 * 1024;
        $fieldCountLimit = $options['field_count_limit'] ?? null;

        $parser = new self($bodySizeLimit, $fieldCountLimit);

        return $parser->parse($request);
    }

    public function has(string $key): bool
    {
        return $this->hasFile($key) || isset($this->data[$key]);
    }

    public function get(string $key, array|string|int|null $default = null): BufferedFile|array|string|int|null
    {
        if ($this->hasFile($key)) {
            return $this->getFile($key, $default);
        }

        return $this->data[$key] ?? $default;

    }

    public function integer(string $key): int|null
    {
        $value = $this->data[$key];

        if (! $value) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]);
    }

    public function getFile(string $key, array|string|int|null $default = null): BufferedFile|null
    {
        return $this->files[$key] ?? $default;
    }

    public function files(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [...$this->data, ...$this->files];
    }

    protected function parse(Request $request): self
    {
        /** @var ConcurrentIterator $iterator */
        $iterator = $this->parser->parseForm($request);

        while ($iterator->continue()) {
            /** @var StreamedField $field */
            $field = $iterator->getValue();

            if ($field->isFile()) {
                $this->files[$field->getName()] = new BufferedFile(
                    $field->getFilename(),
                    $field->buffer(),
                    $field->getMimeType(),
                    $field->getHeaderPairs()
                );
            } else {
                $this->data[$field->getName()] = $field->buffer();
            }
        }

        return $this;
    }
}
