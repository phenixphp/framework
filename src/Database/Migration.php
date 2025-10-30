<?php

declare(strict_types=1);

namespace Phenix\Database;

use Phenix\Database\Migrations\Table;
use Phinx\Migration\AbstractMigration;

abstract class Migration extends AbstractMigration
{
    public function table(string $tableName, array $options = []): Table
    {
        $table = new Table($tableName, $options, $this->getAdapter());
        $this->tables[] = $table;

        return $table;
    }
}
