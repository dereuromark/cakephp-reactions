<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Migrations\BaseMigration;

class PluginReactions extends BaseMigration {

	/**
	 * @return void
	 */
	public function change(): void {
		$type = (string)Configure::read('Polymorphic.type', 'integer');
		$signed = !(bool)Configure::read('Migrations.unsigned_primary_keys', false);

		$polymorphicOptions = [
			'default' => null,
			'null' => false,
		];
		if (in_array($type, ['integer', 'biginteger'], true)) {
			$polymorphicOptions['signed'] = $signed;
		}

		// Reaction keys may be 4-byte emoji; force utf8mb4 on MySQL so they fit
		// regardless of the server/table default charset.
		$reactionOptions = [
			'default' => null,
			'limit' => 32,
			'null' => false,
		];
		if ($this->getAdapter()->getAdapterType() === 'mysql') {
			$reactionOptions['collation'] = 'utf8mb4_unicode_ci';
		}

		$this->table('reactions_reactions')
			->addColumn('foreign_key', $type, $polymorphicOptions)
			->addColumn('model', 'string', [
				'default' => null,
				'limit' => 80,
				'null' => false,
			])
			->addColumn('user_id', 'integer', [
				'default' => null,
				'null' => false,
				'signed' => $signed,
			])
			->addColumn('reaction', 'string', $reactionOptions)
			->addColumn('created', 'datetime', [
				'default' => null,
				'null' => false,
			])
			->addIndex(['user_id'], ['name' => 'reaction_user_id'])
			->addIndex(['model', 'foreign_key'], ['name' => 'reaction_foreign_key'])
			->addIndex(
				['model', 'foreign_key', 'user_id', 'reaction'],
				[
					'name' => 'reaction_unique',
					'unique' => true,
				],
			)
			->create();
	}

}
