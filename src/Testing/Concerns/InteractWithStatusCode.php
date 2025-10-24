<?php

declare(strict_types=1);

namespace Phenix\Testing\Concerns;

use Phenix\Http\Constants\HttpStatus;
use PHPUnit\Framework\Assert;

trait InteractWithStatusCode
{
    public function assertStatusCode(HttpStatus $code): self
    {
        Assert::assertEquals($code->value, $this->response->getStatus());

        return $this;
    }

    public function assertOk(): self
    {
        Assert::assertEquals(HttpStatus::OK->value, $this->response->getStatus());

        return $this;
    }

    public function assertCreated(): self
    {
        Assert::assertEquals(HttpStatus::CREATED->value, $this->response->getStatus());

        return $this;
    }

    public function assertNotFound(): self
    {
        Assert::assertEquals(HttpStatus::NOT_FOUND->value, $this->response->getStatus());

        return $this;
    }

    public function assertNotAcceptable(): self
    {
        Assert::assertEquals(HttpStatus::NOT_ACCEPTABLE->value, $this->response->getStatus());

        return $this;
    }

    public function assertUnprocessableEntity(): self
    {
        Assert::assertEquals(HttpStatus::UNPROCESSABLE_ENTITY->value, $this->response->getStatus());

        return $this;
    }
}
