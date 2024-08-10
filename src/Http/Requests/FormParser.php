<?php

declare(strict_types=1);

namespace Phenix\Http\Requests;

use Amp\Http\Server\FormParser\BufferedFile;
use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\Request;

use function is_array;
use function is_null;
use function is_numeric;

class FormParser extends BodyParser
{
    private Form|null $form;

    public function __construct()
    {
        $this->form = null;
    }

    public static function fromRequest(Request $request, array $options = []): self
    {
        $parser = new self();
        $parser->parse($request);

        return $parser;
    }

    public function get(string $key, array|string|int|null $default = null): BufferedFile|array|string|int|null
    {
        if ($this->hasFile($key)) {
            return $this->getFile($key, $default);
        }

        return $this->form->getValue($key) ?? $default;
    }

    public function has(string $key): bool
    {
        return $this->form->hasFile($key) || ! is_null($this->form->getValue($key));
    }

    public function integer(string $key): int|null
    {
        $value = $this->get($key);

        if (! $value) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    public function hasFile(string $key): bool
    {
        return $this->form->hasFile($key);
    }

    public function getFile(string $key, array|string|int|null $default = null): BufferedFile|null
    {
        return $this->form->getFile($key);
    }

    public function files(): array
    {
        return $this->form->getFiles();
    }

    public function toArray(): array
    {
        return [
            ...$this->prepare($this->form->getValues()),
            ...$this->prepare($this->form->getFiles()),
        ];
    }

    protected function parse(Request $request): self
    {
        $this->form = Form::fromRequest($request);

        return $this;
    }

    private function prepare(array $data): array
    {
        return array_map(function (array|string|int|null $value) {
            if (is_array($value) && count($value) === 1) {
                return array_pop($value);
            }

            return $value;
        }, $data);
    }
}
