<?php

declare(strict_types=1);

namespace Phenix\Http\Requests\Concerns;

use Amp\Http\Server\Trailers;

trait HasTrailers
{
    public function getTrailers(): Trailers|null
    {
        return $this->request->getTrailers();
    }

    public function setTrailers(Trailers $trailers): void
    {
        $this->request->setTrailers($trailers);
    }

    public function removeTrailers(): void
    {
        $this->request->removeTrailers();
    }
}
