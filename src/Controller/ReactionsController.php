<?php

namespace Reactions\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\JsonView;
use InvalidArgumentException;

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

		[$model, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'add');

		$this->Reactions->add($model, $this->foreignKey($entity), $uid, $reaction);

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

		[$model, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'remove');

		$this->Reactions->remove($model, $this->foreignKey($entity), $uid, $reaction);

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

		[$model, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'toggle');

		$foreignKey = $this->foreignKey($entity);
		$result = $this->Reactions->toggle($model, $foreignKey, $uid, $reaction);
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

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @param string|null $alias
	 * @param string|int|null $id
	 * @param string $action
	 *
	 * @return array{0: string, 1: \Cake\Datasource\EntityInterface, 2: int, 3: string}
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

		return [$model, $entity, $uid, $reaction];
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
