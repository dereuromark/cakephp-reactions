<?php

namespace Reactions\Model\Behavior;

use BackedEnum;
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
 * $behavior = $this->Articles->getBehavior('Reactable');
 *
 * // Short, typed API (recommended). These aliases are intentionally not
 * // exposed as table magic methods (their verbs collide too easily with
 * // other behaviors); call them on the behavior instance.
 * $behavior->react($articleId, by: $userId, with: Reaction::ThumbsUp);
 * $behavior->unreact($articleId, by: $userId, with: Reaction::ThumbsUp);
 * $behavior->toggle($articleId, by: $userId, with: Reaction::ThumbsUp);
 *
 * // Array form is still supported and accepts strings or BackedEnum cases
 * $behavior->addReaction([
 *     'modelId' => $articleId,
 *     'userId' => $userId,
 *     'reaction' => Reaction::ThumbsUp,
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
		'counterCache' => false,
		'fieldCounter' => 'reactions_count',
		'implementedFinders' => [
			'reactions' => 'findReactions',
			'reactedBy' => 'findReactedBy',
		],
		// Explicit method allow-list so the short aliases `react()`, `unreact()`,
		// and `toggle()` are NOT auto-registered as table magic methods. Those
		// names are too generic and would collide with other behaviors that
		// expose the same verbs; callers reach them via `$table->getBehavior(
		// 'Reactable')->react(...)` instead, which is the recommended CakePHP
		// 5.3+ form anyway (`$table->methodFromBehavior()` is deprecated).
		'implementedMethods' => [
			'addReaction' => 'addReaction',
			'removeReaction' => 'removeReaction',
			'toggleReaction' => 'toggleReaction',
			'reactionCounts' => 'reactionCounts',
			'userReactions' => 'userReactions',
			'refreshReactionCount' => 'refreshReactionCount',
			'isReactionAllowed' => 'isReactionAllowed',
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
			$this->reactionsTable()->belongsTo((string)$this->getConfig('userModel'), $config['userModelConfig']);
		} elseif (!$this->reactionsTable()->hasAssociation((string)$this->getConfig('userModel'))) {
			$this->reactionsTable()->belongsTo((string)$this->getConfig('userModel'), [
				'className' => $this->getConfig('userModelClass'),
				'foreignKey' => 'user_id',
			]);
		}
		$this->reactionsTable()->setUserAssociation((string)$this->getConfig('userModel'));
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return int|null
	 */
	public function addReaction(array $options = []): ?int {
		$options += ['reaction' => null, 'model' => $this->getConfig('model'), 'modelId' => null, 'userId' => null];
		$this->assertAllowedReaction($options['reaction']);

		$reaction = $this->normalizeReaction($options['reaction']);
		$model = (string)$options['model'];
		$modelId = $this->assertModelId($options['modelId']);
		$entity = $this->reactionsTable()->add(
			$model,
			$modelId,
			(int)$options['userId'],
			$reaction,
		);

		if ($entity !== null) {
			$this->updateCounterCache($model, $modelId);
		}

		return $entity?->id;
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return int
	 */
	public function removeReaction(array $options = []): int {
		$options += ['reaction' => null, 'model' => $this->getConfig('model'), 'modelId' => null, 'userId' => null];
		$this->assertAllowedReaction($options['reaction']);

		$reaction = $this->normalizeReaction($options['reaction']);
		$model = (string)$options['model'];
		$modelId = $this->assertModelId($options['modelId']);
		$deleted = $this->reactionsTable()->remove(
			$model,
			$modelId,
			(int)$options['userId'],
			$reaction,
		);

		if ($deleted > 0) {
			$this->updateCounterCache($model, $modelId);
		}

		return $deleted;
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return array<string, array<string, int>|string>
	 */
	public function toggleReaction(array $options = []): array {
		$options += ['reaction' => null, 'model' => $this->getConfig('model'), 'modelId' => null, 'userId' => null];
		$this->assertAllowedReaction($options['reaction']);

		$reaction = $this->normalizeReaction($options['reaction']);
		$model = (string)$options['model'];
		$modelId = $this->assertModelId($options['modelId']);
		$action = $this->reactionsTable()->toggle(
			$model,
			$modelId,
			(int)$options['userId'],
			$reaction,
		);

		$this->updateCounterCache($model, $modelId);

		return [
			'action' => $action,
			'counts' => $this->reactionCounts($modelId),
		];
	}

	/**
	 * Short, typed API alias for `addReaction()`. Accepts a `string|BackedEnum`
	 * reaction value and skips the array-shape parameter.
	 *
	 * @param string|int $modelId
	 * @param int $by
	 * @param \BackedEnum|string $with
	 *
	 * @return int|null
	 */
	public function react(int|string $modelId, int $by, string|BackedEnum $with): ?int {
		return $this->addReaction([
			'modelId' => $modelId,
			'userId' => $by,
			'reaction' => $with,
		]);
	}

	/**
	 * Short, typed API alias for `removeReaction()`.
	 *
	 * @param string|int $modelId
	 * @param int $by
	 * @param \BackedEnum|string $with
	 *
	 * @return int
	 */
	public function unreact(int|string $modelId, int $by, string|BackedEnum $with): int {
		return $this->removeReaction([
			'modelId' => $modelId,
			'userId' => $by,
			'reaction' => $with,
		]);
	}

	/**
	 * Short, typed API alias for `toggleReaction()`.
	 *
	 * Call via the behavior (`$table->getBehavior('Reactable')->toggle(...)`).
	 * The alias is NOT exposed as a table magic method — `toggle` is too
	 * generic and would collide with other behaviors that expose the same
	 * verb (CakePHP's BehaviorRegistry rejects duplicate implemented methods
	 * at load time). The same applies to `react()` and `unreact()`.
	 *
	 * @param string|int $modelId
	 * @param int $by
	 * @param \BackedEnum|string $with
	 *
	 * @return array<string, array<string, int>|string>
	 */
	public function toggle(int|string $modelId, int $by, string|BackedEnum $with): array {
		return $this->toggleReaction([
			'modelId' => $modelId,
			'userId' => $by,
			'reaction' => $with,
		]);
	}

	/**
	 * @param string|int $modelId
	 *
	 * @return array<string, int>
	 */
	public function reactionCounts(int|string $modelId): array {
		return $this->reactionsTable()->counts((string)$this->getConfig('model'), $modelId);
	}

	/**
	 * @param string|int $modelId
	 * @param int $userId
	 *
	 * @return array<string>
	 */
	public function userReactions(int|string $modelId, int $userId): array {
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
	 * Checks whether a reaction value is valid for this behavior config.
	 *
	 * This is useful when app code intentionally uses lower-level
	 * `ReactionsTable` methods and wants to preflight a user-supplied reaction
	 * against the same rules the behavior uses before writing.
	 *
	 * @param mixed $reaction
	 *
	 * @return bool
	 */
	public function isReactionAllowed(mixed $reaction): bool {
		try {
			$this->assertAllowedReaction($reaction);
		} catch (BadRequestException) {
			return false;
		}

		return true;
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
		if ($allowed === null || !is_array($allowed)) {
			return;
		}

		$needle = $this->normalizeReaction($reaction);
		foreach ($allowed as $entry) {
			$entryValue = $entry instanceof BackedEnum ? (string)$entry->value : $entry;
			if ($needle === $entryValue) {
				return;
			}
		}

		throw new BadRequestException('Invalid reaction key');
	}

	/**
	 * Normalize a reaction value supplied as a plain string or a string-backed
	 * enum case down to the string stored in `reactions_reactions.reaction`.
	 *
	 * @param mixed $reaction
	 *
	 * @return string
	 */
	protected function normalizeReaction(mixed $reaction): string {
		if ($reaction instanceof BackedEnum) {
			return (string)$reaction->value;
		}
		if (is_string($reaction) && $reaction !== '') {
			return $reaction;
		}

		throw new BadRequestException('Invalid reaction');
	}

	/**
	 * Public entry point so the controller flow can keep the counter in sync
	 * after calls that bypass the behavior (`ReactionsController::add/remove/
	 * toggle/delete` go through `ReactionsTable` directly).
	 *
	 * `$model` is the value stored in `reactions_reactions.model` for the row
	 * just written; the count uses that to scope the SELECT. The write target
	 * is always `$this->_table` (the table this behavior is loaded on), so the
	 * caller is responsible for invoking this on the correct host table.
	 * Unlike the internal `updateCounterCache()` path, this method does not
	 * require `$model` to match the behavior's configured model — it is meant
	 * for cases where the stored model string differs from the host table's
	 * alias (e.g., `Reactions.models.Posts => 'Blog.Posts'`).
	 *
	 * @param string $model
	 * @param string|int $modelId
	 *
	 * @return void
	 */
	public function refreshReactionCount(string $model, int|string $modelId): void {
		if (!$this->getConfig('counterCache')) {
			return;
		}

		$field = (string)$this->getConfig('fieldCounter');
		if ($field === '' || !$this->_table->getSchema()->hasColumn($field)) {
			return;
		}

		$count = $this->reactionsTable()->find()
			->where([
				'model' => $model,
				'foreign_key' => $modelId,
			])
			->count();

		$primaryKey = $this->_table->getPrimaryKey();
		if (is_array($primaryKey)) {
			$primaryKey = $primaryKey[0];
		}

		$this->_table->updateAll(
			[$field => $count],
			[$primaryKey => $modelId],
		);
	}

	/**
	 * Internal path used by the behavior's own add/remove/toggle methods. Skips
	 * the update when the caller's `model` option targets a different model than
	 * the one this behavior is loaded on — the counter belongs to `$this->_table`
	 * and updating it from another model's reaction set would corrupt it.
	 *
	 * @param string $model
	 * @param string|int $modelId
	 *
	 * @return void
	 */
	protected function updateCounterCache(string $model, int|string $modelId): void {
		if ($model !== $this->getConfig('model')) {
			return;
		}

		$this->refreshReactionCount($model, $modelId);
	}

	/**
	 * @param mixed $modelId
	 *
	 * @return string|int
	 */
	protected function assertModelId(mixed $modelId): int|string {
		if (is_int($modelId)) {
			return $modelId;
		}
		if (is_string($modelId) && $modelId !== '') {
			return $modelId;
		}

		throw new BadRequestException('Invalid modelId');
	}

}
