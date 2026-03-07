<?php

declare(strict_types=1);

namespace Phenix\Database;

use League\Uri\Components\Query;
use League\Uri\Http;
use Phenix\Contracts\Arrayable;
use Phenix\Data\Collection;
use Phenix\Facades\Url;

class Paginator implements Arrayable
{
    private Query $query;
    private int $itemsEachSide = 2;
    private int $linksNumber = 5;
    private readonly int $lastPage;

    private bool $withQueryParameters = true;

    public function __construct(
        private Http $uri,
        private Collection $data,
        private readonly int $total,
        private readonly int $currentPage,
        private readonly int $perPage
    ) {
        $this->query = Query::fromUri($this->uri);
        $this->lastPage = (int) ceil($this->total / $this->perPage);
    }

    public function withoutQueryParameters(): self
    {
        $this->withQueryParameters = false;

        return $this;
    }

    public function data(): Collection
    {
        return $this->data;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function hasPreviousPage()
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage()
    {
        return $this->currentPage < $this->lastPage;
    }

    public function from(): int|null
    {
        if ($this->total === 0) {
            return null;
        }

        return (($this->currentPage - 1) * $this->perPage) + 1;
    }

    public function to(): int
    {
        if ($this->hasNextPage()) {
            return $this->currentPage * $this->perPage;
        }

        return $this->total;
    }

    public function links(): array
    {
        if ($this->total === 0 || $this->lastPage === 0) {
            return [];
        }

        $links = [];
        $separator = ['url' => null, 'label' => '...'];

        $start = max(1, $this->currentPage - $this->itemsEachSide);
        $end = min($this->lastPage, $this->currentPage + $this->itemsEachSide);

        if ($this->currentPage <= ($this->linksNumber - 1)) {
            $start = 1;
            $end = min($this->lastPage, $this->linksNumber);
        }

        if ($start > 1) {
            $links[] = $this->buildLink(1);

            if ($start > 2) {
                $links[] = $separator;
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $links[] = $this->buildLink($i);
        }

        if ($end < $this->lastPage) {
            if ($end < ($this->lastPage - 1)) {
                $links[] = $separator;
            }

            $links[] = $this->buildLink($this->lastPage);
        }

        return $links;
    }

    public function toArray(): array
    {
        return [
            'path' => Url::to($this->uri->getPath()),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'first_page_url' => $this->getFirstPageUrl(),
            'last_page_url' => $this->getLastPageUrl(),
            'prev_page_url' => $this->getPrevPageUrl(),
            'next_page_url' => $this->getNextPageUrl(),
            'from' => $this->from(),
            'to' => $this->to(),
            'data' => $this->data->toArray(),
            'links' => $this->links(),
        ];
    }

    private function getQueryParameters(): array
    {
        return $this->withQueryParameters
            ? $this->query->parameters()
            : [];
    }

    private function buildLink(int $page, string|int|null $label = null): array
    {
        return ['url' => $this->buildPageUrl($page), 'label' => $label ?? $page];
    }

    private function buildPageUrl(int $page): string
    {
        $parameters['page'] = $page;

        if ($this->query->has('per_page')) {
            $parameters['per_page'] = $this->perPage;
        }

        $parameters = array_merge($this->getQueryParameters(), $parameters);

        return Url::to($this->uri->getPath(), $parameters);
    }

    private function getFirstPageUrl(): string
    {
        return $this->buildPageUrl(1);
    }

    private function getLastPageUrl(): string|null
    {
        if ($this->lastPage === 0) {
            return null;
        }

        return $this->buildPageUrl($this->lastPage);
    }

    private function getPrevPageUrl(): string|null
    {
        return $this->hasPreviousPage() ? $this->buildPageUrl($this->currentPage - 1) : null;
    }

    private function getNextPageUrl(): string|null
    {
        return $this->hasNextPage() ? $this->buildPageUrl($this->currentPage + 1) : null;
    }
}
