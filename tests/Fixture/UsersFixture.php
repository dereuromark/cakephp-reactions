<?php

declare(strict_types=1);

namespace Reactions\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture {

    // phpcs:disable
    public array $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true],
        'name' => ['type' => 'string', 'length' => 140, 'null' => false, 'default' => null, 'comment' => ''],
        'email' => ['type' => 'string', 'length' => 190, 'null' => true, 'default' => null, 'comment' => ''],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'email' => ['type' => 'unique', 'columns' => ['email'], 'length' => []],
        ],
    ];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Mark',
                'email' => 'mark@example.com',
            ],
            [
                'id' => 2,
                'name' => 'Jane',
                'email' => 'jane@example.com',
            ],
        ];
        parent::init();
    }
}
