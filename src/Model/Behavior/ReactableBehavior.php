<?php

namespace Reactions\Model\Behavior;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Reactions\Model\Table\ReactionsTable;

/**
 * ReactableBehavior - Adds GitHub-style reactions to any model.
 *
 * Usage:
 * ```php
 * $this->addBehavior('Reactions.Reactable');
 *
 * $this->Articles->addReaction([
 *     'modelId' => $articleId,
 *     'userId' => $userId,
 *     'reaction' => 'thumbsup',
 * ]);
 *
 * $this->Articles->toggleReaction([
 *     'modelId' => $articleId,
 *     'userId' => $userId,
 *     'reaction' => 'thumbsup',
 * ]);
 *
 * $this->Articles->find('reactions', id: $articleId);
 * ```
 */
class ReactableBehavior extends Behavior {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'modelClass' => null,
		'reactionClass' => 'Reactions.Reactions',
		'userModelClass' => 'Users',
		'userModelConfig' => null,
		'allowed' => null,
		'implementedFinders' => [
			'reactions' => 'findReactions',
			'reactedBy' => 'findReactedBy',
		],
	];

	/**
	 * @param \Cake\ORM\Table $table
	 * @param array<string, mixed> $config
	 */
	public function __construct(Table $table, array $config = []) {
		$config += (array)Configure::read('Reactions');

		parent::__construct($table, $config);
	}

	/**
	 * @param array $config
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		if (!$this->getConfig('model')) {
			$this->setConfig('model', $this->_table->getAlias());
		}
		if (!$this->getConfig('modelClass')) {
			$this->setConfig('modelClass', $this->_table->getRegistryAlias());
		}
		if (!$this->getConfig('userModel')) {
			[, $alias] = pluginSplit((string)$this->getConfig('userModelClass'));
			$this->setConfig('userModel', $alias);
		}

		$this->_table->hasMany('Reactions', [
			'className' => $this->getConfig('reactionClass'),
			'foreignKey' => 'foreign_key',
			'order' => 'Reactions.created DESC',
			'conditions' => ['Reactions.model' => $this->getConfig('model')],
			'dependent' => true,
		]);

		if (!empty($config['userId'])) {
			$this->_table->hasOne('Reaction', [
				'className' => $this->getConfig('reactionClass'),
				'foreignKey' => 'foreign_key',
				'conditions' => ['Reaction.model' => $this->getConfig('model'), 'Reaction.user_id' => $config['userId']],
				'dependent' => true,
			]);
		}

		$this->reactionsTable()->belongsTo((string)$this->getConfig('modelClass'), [
			'className' => $this->getConfig('modelClass'),
			'foreignKey' => 'foreign_key',
		]);

		if ($this->getConfig('userModelConfig') && !$this->reactionsTable()->hasAssociation((string)$this->getConfig('userModel'))) {
			$this->reactionsTable()->belongsTo($config['userModel'], $config['userModelConfig']);
		} elseif (!$this->reactionsTable()->hasAssociation((string)$this->getConfig('userModel'))) {
			$this->reactionsTable()->belongsTo((string)$this->getConfig('userModel'), [
				'className' => $this->getConfig('userModelClass'),
				'foreignKey' => 'user_id',
			]);
		}
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return int|null
	 */
	public function addReaction(array $options = []): ?int {
		$options += ['reaction' => null, 'model' => $this->getConfig('model'), 'modelId' => null, 'userId' => null];
		$this->assertAllowedReaction($options['reaction']);

		$entity = $this->reactionsTable()->add($options['model'], (int)$options['modelId'], (int)$options['userId'], (string)$options['reaction']);
		if (!$entity->isNew()) {
			return $entity->id;
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return int
	 */
	public function removeReaction(array $options = []): int {
		$options += ['reaction' => null, 'model' => $this->getConfig('model'), 'modelId' => null, 'userId' => null];
		$this->assertAllowedReaction($options['reaction']);

		return $this->reactionsTable()->remove($options['model'], (int)$options['modelId'], (int)$options['userId'], (string)$options['reaction']);
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return array<string, array<string, int>|string>
	 */
	public function toggleReaction(array $options = []): array {
		$options += ['reaction' => null, 'model' => $this->getConfig('model'), 'modelId' => null, 'userId' => null];
		$this->assertAllowedReaction($options['reaction']);

		$action = $this->reactionsTable()->toggle($options['model'], (int)$options['modelId'], (int)$options['userId'], (string)$options['reaction']);

		return [
			'action' => $action,
			'counts' => $this->reactionCounts((int)$options['modelId']),
		];
	}

	/**
	 * @param int $modelId
	 *
	 * @return array<string, int>
	 */
	public function reactionCounts(int $modelId): array {
		return $this->reactionsTable()->counts((string)$this->getConfig('model'), $modelId);
	}

	/**
	 * @param int $modelId
	 * @param int $userId
	 *
	 * @return array<string>
	 */
	public function userReactions(int $modelId, int $userId): array {
		$query = $this->reactionsTable()->find();

		/** @var array<string> $reactions */
		$reactions = $query
			->select(['reaction'])
			->where([
				'model' => $this->getConfig('model'),
				'foreign_key' => $modelId,
				'user_id' => $userId,
			])
			->enableHydration(false)
			->all()
			->extract('reaction')
			->toList();

		return $reactions;
	}

	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array<string, mixed> $options
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findReactions(SelectQuery $query, array $options = []): SelectQuery {
		$primaryKey = $this->_table->getPrimaryKey();
		if (is_array($primaryKey)) {
			$primaryKey = $primaryKey[0];
		}

		return $query
			->where([$this->_table->aliasField($primaryKey) => $options['id']])
			->contain([
				'Reactions' => function (SelectQuery $q): SelectQuery {
					return $q->contain((string)$this->getConfig('userModel'));
				},
			]);
	}

	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array<string, mixed> $options
	 *
	 * @return \Cake\ORM\Query\SelectQuery
	 */
	public function findReactedBy(SelectQuery $query, array $options = []): SelectQuery {
		return $query->matching('Reactions', function (SelectQuery $q) use ($options) {
			return $q->where(['Reactions.user_id' => $options['userId']]);
		});
	}

	/**
	 * @return \Reactions\Model\Table\ReactionsTable
	 */
	protected function reactionsTable(): ReactionsTable {
		/** @var \Reactions\Model\Table\ReactionsTable */
		return $this->_table->Reactions->getTarget();
	}

	/**
	 * @param mixed $reaction
	 *
	 * @return void
	 */
	protected function assertAllowedReaction(mixed $reaction): void {
		$allowed = $this->getConfig('allowed');
		if ($allowed !== null && is_array($allowed) && !in_array($reaction, $allowed, true)) {
			throw new BadRequestException('Invalid reaction key');
		}
	}

}
