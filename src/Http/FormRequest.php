<?php

declare(strict_types=1);

namespace Phenix\Http;

use Amp\Http\Server\Request as ServerRequest;
use Phenix\Validation\Validator;

abstract class FormRequest extends Request
{
    private bool $isValid;
    private bool $checked;
    protected Validator $validator;

    public function __construct(ServerRequest $request)
    {
        parent::__construct($request);

        $this->checked = false;
        $this->validator = new Validator();
    }

    abstract protected function rules(): array;

    public function isValid(): bool
    {
        if ($this->checked) {
            return $this->isValid;
        }

        $this->validator->setRules($this->rules());
        $this->validator->setData($this->toArray());

        $this->checked = true;

        return $this->isValid = $this->validator->passes();
    }

    public function errors(): array
    {
        return $this->validator->failing();
    }

    public function validated(): array
    {
        return $this->validator->validated();
    }
}
