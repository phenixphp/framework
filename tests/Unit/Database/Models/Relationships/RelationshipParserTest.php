<?php

declare(strict_types=1);

use Phenix\Database\Models\Relationships\BelongsTo;
use Phenix\Database\Models\Relationships\RelationshipParser;

it('parse single relationship', function () {
    $parser = new RelationshipParser([
        'user',
    ]);

    $parser->parse();

    expect($parser->toArray())->toBe([
        'user' => [
            'columns' => ['*'],
            'relationships' => [],
        ],
    ]);
});

it('parse single relationship with closure', function () {
    $closure = function (BelongsTo $belongsTo) {
        $belongsTo->query()
            ->select(['id', 'name']);
    };

    $parser = new RelationshipParser([
        'user' => $closure,
    ]);

    $parser->parse();

    expect($parser->toArray())->toBe([
        'user' => [
            'columns' => $closure,
            'relationships' => [],
        ],
    ]);
});

it('parse multiple relationships', function () {
    $parser = new RelationshipParser([
        'user',
        'posts',
    ]);

    $parser->parse();

    expect($parser->toArray())->toBe([
        'user' => [
            'columns' => ['*'],
            'relationships' => [],
        ],
        'posts' => [
            'columns' => ['*'],
            'relationships' => [],
        ],
    ]);
});

it('parse relationships with dot notation in second level', function () {
    $parser = new RelationshipParser([
        'user.company',
    ]);

    $parser->parse();

    expect($parser->toArray())->toBe([
        'user' => [
            'columns' => ['*'],
            'relationships' => [
                'company' => [
                    'columns' => ['*'],
                    'relationships' => [],
                ],
            ],
        ],
    ]);
});

it('parse relationships with dot notation in nested level', function () {
    $parser = new RelationshipParser([
        'user',
        'user.company',
        'user.company.account',
    ]);

    $parser->parse();

    expect($parser->toArray())->toBe([
        'user' => [
            'columns' => ['*'],
            'relationships' => [
                'company' => [
                    'columns' => ['*'],
                    'relationships' => [
                        'account' => [
                            'columns' => ['*'],
                            'relationships' => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);
});

it('parse relationships with dot notation in nested level with column selection', function () {
    $parser = new RelationshipParser([
        'user:id,name,company_id',
        'user.company:id,name,account_id',
        'user.company.account:id,name',
    ]);

    $parser->parse();

    dump($parser->toArray());

    expect($parser->toArray())->toBe([
        'user' => [
            'columns' => ['id', 'name', 'company_id'],
            'relationships' => [
                'company' => [
                    'columns' => ['id', 'name', 'account_id'],
                    'relationships' => [
                        'account' => [
                            'columns' => ['id', 'name'],
                            'relationships' => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);
});
