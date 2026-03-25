<?php

declare(strict_types=1);

namespace Phenix\Database\Migrations\Columns;

use Phinx\Db\Adapter\MysqlAdapter;

class Blob extends Column
{
    public function __construct(
        protected string $name,
        protected int|null $limit = null
    ) {
        parent::__construct($name);

        if ($limit !== null) {
            $this->options['limit'] = $limit;
        }
    }

    public function getType(): string
    {
        return 'blob';
    }

    public function limit(int $limit): static
    {
        $this->options['limit'] = $limit;

        return $this;
    }

    public function tiny(): static
    {
        if ($this->isMysql()) {
            $this->options['limit'] = MysqlAdapter::BLOB_TINY;
        }

        return $this;
    }

    public function regular(): static
    {
        if ($this->isMysql()) {
            $this->options['limit'] = MysqlAdapter::BLOB_REGULAR;
        }

        return $this;
    }

    public function medium(): static
    {
        if ($this->isMysql()) {
            $this->options['limit'] = MysqlAdapter::BLOB_MEDIUM;
        }

        return $this;
    }

    public function long(): static
    {
        if ($this->isMysql()) {
            $this->options['limit'] = MysqlAdapter::BLOB_LONG;
        }

        return $this;
    }
}
