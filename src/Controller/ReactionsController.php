<?php

namespace Reactions\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\Table;
use Cake\View\JsonView;
use InvalidArgumentException;
use Throwable;

/**
 * @property \Reactions\Model\Table\ReactionsTable $Reactions
 */
class ReactionsController extends AppController {

	use AuthTrait;

	protected ?string $defaultTable = 'Reactions.Reactions';

	/**
	 * @return class-string<\Cake\View\View>[]
	 */
	public function viewClasses(): array {
		return [JsonView::class];
	}

	/**
	 * @param string|null $alias
	 * @param string|int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function add(?string $alias = null, string|int|null $id = null): ?Response {
		$this->request->allowMethod(['post', 'put', 'patch']);

		[$model, $table, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'add');
		$foreignKey = $this->foreignKey($entity);

		$this->Reactions->add($model, $foreignKey, $uid, $reaction);
		$this->refreshReactionCount($table, $model, $foreignKey);

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @param string|null $alias
	 * @param string|int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function remove(?string $alias = null, string|int|null $id = null): ?Response {
		$this->request->allowMethod(['post', 'delete']);

		[$model, $table, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'remove');
		$foreignKey = $this->foreignKey($entity);

		$this->Reactions->remove($model, $foreignKey, $uid, $reaction);
		$this->refreshReactionCount($table, $model, $foreignKey);

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @param string|null $alias
	 * @param string|int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function toggle(?string $alias = null, string|int|null $id = null): ?Response {
		$this->request->allowMethod(['post']);

		[$model, $table, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'toggle');

		$foreignKey = $this->foreignKey($entity);
		$result = $this->Reactions->toggle($model, $foreignKey, $uid, $reaction);
		$this->refreshReactionCount($table, $model, $foreignKey);
		$counts = $this->Reactions->counts($model, $foreignKey);

		if ($this->request->is('json') || $this->request->is('ajax')) {
			$this->viewBuilder()->setClassName(JsonView::class);
			$this->viewBuilder()->setOption('serialize', ['action', 'counts']);
			$this->set([
				'action' => $result,
				'counts' => $counts,
			]);

			return null;
		}

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @param string|int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function delete(string|int|null $id = null): ?Response {
		$this->request->allowMethod(['post', 'delete']);

		$id = $this->request->getData('id') ?: $id;
		$reaction = $this->Reactions->get($id);

		$userId = $this->userId();
		if ($reaction->user_id !== $userId) {
			throw new NotFoundException(__d('reactions', 'You are not authorized to remove this reaction.'));
		}

		$this->Reactions->delete($reaction);

		$model = $reaction->model;
		if ($model !== '') {
			try {
				$table = $this->fetchTable($model);
				$this->refreshReactionCount($table, $model, $reaction->foreign_key);
			} catch (Throwable) {
				// host table no longer resolvable; counter cannot be refreshed
			}
		}

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @param string|null $alias
	 * @param string|int|null $id
	 * @param string $action
	 *
	 * @return array{0: string, 1: \Cake\ORM\Table, 2: \Cake\Datasource\EntityInterface, 3: int, 4: string}
	 */
	protected function resolveReactionRequest(?string $alias, string|int|null $id, string $action): array {
		$model = Configure::read('Reactions.models.' . $alias);
		if (!$model) {
			throw new NotFoundException('Invalid alias');
		}
		$table = $this->fetchTable($model);
		$entity = $table->get($id);

		$uid = $this->userId();
		if (!$uid) {
			throw new MethodNotAllowedException('Must be logged in to ' . $action . ' reaction');
		}

		$reaction = $this->request->getData('reaction');
		if (!is_string($reaction) || $reaction === '') {
			throw new InvalidArgumentException('Missing reaction for Reaction ' . $alias . ':' . $id);
		}
		$this->assertAllowedReaction($table, $reaction);

		return [$model, $table, $entity, $uid, $reaction];
	}

	/**
	 * @param \Cake\ORM\Table $table
	 * @param string $reaction
	 *
	 * @return void
	 */
	protected function assertAllowedReaction(Table $table, string $reaction): void {
		if ($table->behaviors()->has('Reactable')) {
			$allowed = $table->getBehavior('Reactable')->getConfig('allowed');
		} else {
			$allowed = Configure::read('Reactions.allowed');
		}
		if ($allowed !== null && is_array($allowed) && !in_array($reaction, $allowed, true)) {
			throw new BadRequestException('Invalid reaction key');
		}
	}

	/**
	 * Forwards to the host table's `Reactable` behavior so an enabled counter
	 * cache stays in sync with the reaction row this controller just wrote
	 * directly via `ReactionsTable`. Silent when the behavior is not loaded
	 * or when `counterCache` is disabled.
	 *
	 * `$model` is the value stored in `reactions_reactions.model` for the row
	 * just written; the count is scoped to that string so the refresh works
	 * even when `Reactions.models` maps a public alias to a different class
	 * name (the stored value diverges from the host table's alias).
	 *
	 * @param \Cake\ORM\Table $table
	 * @param string $model
	 * @param string|int $modelId
	 *
	 * @return void
	 */
	protected function refreshReactionCount(Table $table, string $model, int|string $modelId): void {
		if (!$table->behaviors()->has('Reactable')) {
			return;
		}

		/** @var \Reactions\Model\Behavior\ReactableBehavior $behavior */
		$behavior = $table->getBehavior('Reactable');
		$behavior->refreshReactionCount($model, $modelId);
	}

	/**
	 * Returns the entity's primary key value untouched (int or string)
	 * so polymorphic foreign keys (uuid, bigint) survive the round-trip
	 * to the reactions table without being narrowed to int.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 *
	 * @return string|int
	 */
	protected function foreignKey(EntityInterface $entity): int|string {
		$id = $entity->get('id');
		if (is_int($id) || is_string($id)) {
			return $id;
		}

		throw new InvalidArgumentException('Reactable entity has invalid primary key type');
	}

}
