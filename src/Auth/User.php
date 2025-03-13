<?php

declare(strict_types=1);

namespace Phenix\Auth;

use Phenix\Database\Models\DatabaseModel;

abstract class User extends DatabaseModel
{
    // public function createToken($name, array $habilities = []): string
    // {
    //     return $this->tokens()->create([
    //         'name' => $name,
    //         'token' => hash('sha256', $token = random_bytes(40)),
    //         'habilities' => $habilities,
    //     ])->token;
    // }
}
