<?php

namespace Reactions\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Reactions\Model\Entity\Reaction;

/**
 * Reactions Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \Reactions\Model\Entity\Reaction newEmptyEntity()
 * @method \Reactions\Model\Entity\Reaction newEntity(array $data, array $options = [])
 * @method array<\Reactions\Model\Entity\Reaction> newEntities(array $data, array $options = [])
 * @method \Reactions\Model\Entity\Reaction get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Reactions\Model\Entity\Reaction findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Reactions\Model\Entity\Reaction patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Reactions\Model\Entity\Reaction> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Reactions\Model\Entity\Reaction|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Reactions\Model\Entity\Reaction saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Reactions\Model\Entity\Reaction>|false saveMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Reactions\Model\Entity\Reaction> saveManyOrFail(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Reactions\Model\Entity\Reaction>|false deleteMany(iterable $entities, array $options = [])
 * @method \Cake\Datasource\ResultSetInterface<\Reactions\Model\Entity\Reaction> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ReactionsTable extends Table {

	/**
	 * @param array $config
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->setTable('reactions_reactions');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->addBehavior('Timestamp');

		$this->belongsTo('Users', ['className' => Configure::read('Reactions.userModelClass') ?: 'Users']);
	}

	/**
	 * @param \Cake\Validation\Validator $validator
	 *
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		$validator->notEmptyString('model');
		$validator->requirePresence('model', 'create');
		$validator->notEmptyString('foreign_key');
		$validator->requirePresence('foreign_key', 'create');
		$validator->notEmptyString('reaction');
		$validator->requirePresence('reaction', 'create');

		return $validator;
	}

	/**
	 * @param \Cake\ORM\RulesChecker $rules
	 *
	 * @return \Cake\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['user_id'], 'Users'));

		return $rules;
	}

	/**
	 * @param string $model
	 * @param int $foreignKey
	 * @param int $userId
	 * @param string $reaction
	 *
	 * @return \Reactions\Model\Entity\Reaction
	 */
	public function add(string $model, int $foreignKey, int $userId, string $reaction): Reaction {
		$data = [
			'model' => $model,
			'foreign_key' => $foreignKey,
			'user_id' => $userId,
			'reaction' => $reaction,
		];

		$entity = $this->findOrCreate($data);
		if ($entity->hasErrors()) {
			return $entity;
		}

		$this->saveOrFail($entity);

		return $entity;
	}

	/**
	 * @param string $model
	 * @param int $foreignKey
	 * @param int $userId
	 * @param string $reaction
	 *
	 * @return int
	 */
	public function remove(string $model, int $foreignKey, int $userId, string $reaction): int {
		return $this->deleteAll([
			'model' => $model,
			'foreign_key' => $foreignKey,
			'user_id' => $userId,
			'reaction' => $reaction,
		]);
	}

	/**
	 * @param string $model
	 * @param int $foreignKey
	 * @param int $userId
	 * @param string $reaction
	 *
	 * @return string
	 */
	public function toggle(string $model, int $foreignKey, int $userId, string $reaction): string {
		$conditions = [
			'model' => $model,
			'foreign_key' => $foreignKey,
			'user_id' => $userId,
			'reaction' => $reaction,
		];
		$entity = $this->find()->select(['id'])->where($conditions)->first();
		if ($entity) {
			$this->deleteOrFail($entity);

			return 'removed';
		}

		$this->add($model, $foreignKey, $userId, $reaction);

		return 'added';
	}

	/**
	 * @param string $model
	 * @param int $foreignKey
	 *
	 * @return array<string, int>
	 */
	public function counts(string $model, int $foreignKey): array {
		$query = $this->find();

		/** @var array<string, int> $result */
		$result = $query
			->select([
				'reaction',
				'count' => $query->func()->count('*'),
			])
			->where([
				'model' => $model,
				'foreign_key' => $foreignKey,
			])
			->groupBy(['reaction'])
			->enableHydration(false)
			->all()
			->combine('reaction', 'count')
			->toArray();

		return array_map('intval', $result);
	}

	/**
	 * @param string $model
	 *
	 * @return int
	 */
	public function reset(string $model): int {
		return $this->deleteAll(['model' => $model]);
	}

}
