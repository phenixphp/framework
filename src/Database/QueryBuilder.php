<?php

declare(strict_types=1);

namespace Phenix\Database;

use Amp\Sql\Common\SqlCommonConnectionPool;
use Phenix\App;
use Phenix\Data\Collection;
use Phenix\Database\Concerns\Query\BuildsQuery;
use Phenix\Database\Concerns\Query\HasJoinClause;
use Phenix\Database\Concerns\Query\HasSentences;
use Phenix\Database\Constants\Actions;
use Phenix\Database\Constants\Connections;

use function is_string;

class QueryBuilder extends QueryBase
{
    use BuildsQuery, HasSentences {
        HasSentences::count insteadof BuildsQuery;
        HasSentences::insert insteadof BuildsQuery;
        HasSentences::exists insteadof BuildsQuery;
        HasSentences::doesntExist insteadof BuildsQuery;
        HasSentences::update insteadof BuildsQuery;
        HasSentences::delete insteadof BuildsQuery;
        BuildsQuery::insert as protected insertRows;
        BuildsQuery::insertOrIgnore as protected insertOrIgnoreRows;
        BuildsQuery::upsert as protected upsertRows;
        BuildsQuery::insertFrom as protected insertFromRows;
        BuildsQuery::update as protected updateRow;
        BuildsQuery::delete as protected deleteRows;
        BuildsQuery::count as protected countRows;
        BuildsQuery::exists as protected existsRows;
        BuildsQuery::doesntExist as protected doesntExistRows;
    }
    use HasJoinClause;

    protected SqlCommonConnectionPool $connection;

    public function __construct()
    {
        parent::__construct();

        $this->connection = App::make(Connections::default());
    }

    public function connection(SqlCommonConnectionPool|string $connection): self
    {
        if (is_string($connection)) {
            $connection = App::make(Connections::name($connection));
        }

        $this->connection = $connection;

        return $this;
    }

    /**
     * @return Collection<int, array>
     */
    public function get(): Collection
    {
        $this->action = Actions::SELECT;

        [$dml, $params] = $this->toSql();

        $result = $this->connection->prepare($dml)
            ->execute($params);

        $collection = new Collection('array');

        foreach ($result as $row) {
            $collection->add($row);
        }

        return $collection;
    }

    /**
     * @return array<string, mixed>
     */
    public function first(): array
    {
        $this->action = Actions::SELECT;

        $this->limit(1);

        return $this->get()->first();
    }
}
