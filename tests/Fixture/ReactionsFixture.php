<?php
declare(strict_types=1);

namespace Reactions\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ReactionsFixture extends TestFixture {

	/**
	 * @var string
	 */
	public string $table = 'reactions_reactions';

	/**
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer', 'length' => null, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true],
		'foreign_key' => ['type' => 'integer', 'length' => null, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => ''],
		'model' => ['type' => 'string', 'length' => 80, 'null' => false, 'default' => '', 'comment' => ''],
		'user_id' => ['type' => 'integer', 'length' => null, 'unsigned' => true, 'null' => false, 'default' => null, 'comment' => ''],
		'reaction' => ['type' => 'string', 'length' => 32, 'null' => false, 'default' => '', 'comment' => ''],
		'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => ''],
		'_indexes' => [
			'reaction_user_id' => ['type' => 'index', 'columns' => ['user_id'], 'length' => []],
			'reaction_foreign_key' => ['type' => 'index', 'columns' => ['model', 'foreign_key'], 'length' => []],
		],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
			'reaction_unique' => ['type' => 'unique', 'columns' => ['model', 'foreign_key', 'user_id', 'reaction'], 'length' => []],
		],
	];

	/**
	 * @return void
	 */
	public function init(): void {
		$this->records = [
			[
				'foreign_key' => 1,
				'model' => 'Posts',
				'user_id' => 1,
				'reaction' => '👍',
				'created' => '2024-03-13 02:01:23',
			],
			[
				'foreign_key' => 1,
				'model' => 'Posts',
				'user_id' => 2,
				'reaction' => '🎉',
				'created' => '2024-03-13 02:02:23',
			],
		];
		parent::init();
	}

}
