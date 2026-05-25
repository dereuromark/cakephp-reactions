<?php

declare(strict_types=1);

namespace Reactions\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PostsFixture extends TestFixture {

    // phpcs:disable
    public array $fields = [
        'id' => ['type' => 'integer', 'length' => null, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true],
        'title' => ['type' => 'string', 'length' => 190, 'null' => false, 'default' => null, 'comment' => ''],
        'content' => ['type' => 'string', 'length' => 190, 'null' => true, 'default' => null, 'comment' => ''],
		'count' => ['type' => 'integer', 'length' => null, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => ''],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
    ];

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'title' => 'Lorem ipsum dolor sit amet',
                'content' => 'Lorem ipsum dolor sit amet',
            ],
            [
                'id' => 2,
                'title' => 'Second post without reactions',
                'content' => 'Second post without reactions',
            ],
        ];
        parent::init();
    }
}
