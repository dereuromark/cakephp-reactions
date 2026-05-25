<?php

namespace Reactions\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\JsonView;
use InvalidArgumentException;
use TinyAuth\Controller\Component\AuthComponent;
use TinyAuth\Controller\Component\AuthUserComponent;

/**
 * @property \Reactions\Model\Table\ReactionsTable $Reactions
 * @property \TinyAuth\Controller\Component\AuthUserComponent $AuthUser
 * @property \TinyAuth\Controller\Component\AuthComponent $Auth
 */
class ReactionsController extends AppController {

	use AuthTrait;

	protected ?string $defaultTable = 'Reactions.Reactions';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		if (class_exists(AuthUserComponent::class)) {
			$this->loadComponent('TinyAuth.AuthUser');
		} elseif (class_exists(AuthComponent::class)) {
			$this->loadComponent('TinyAuth.Auth');
		}
	}

	/**
	 * @return class-string<\Cake\View\View>[]
	 */
	public function viewClasses(): array {
		return [JsonView::class];
	}

	/**
	 * @param string|null $alias
	 * @param int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function add(?string $alias = null, ?int $id = null): ?Response {
		$this->request->allowMethod(['post', 'put', 'patch']);

		[$model, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'add');

		$result = $this->Reactions->add($model, (int)$entity->get('id'), $uid, $reaction);
		if ($result->hasErrors()) {
			$this->Flash->error(__d('reactions', 'Could not save reaction, please try again.'));
		}

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @param string|null $alias
	 * @param int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function remove(?string $alias = null, ?int $id = null): ?Response {
		$this->request->allowMethod(['post', 'delete']);

		[$model, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'remove');

		$this->Reactions->remove($model, (int)$entity->get('id'), $uid, $reaction);

		return $this->redirect($this->referer(['action' => 'index']));
	}

	/**
	 * @param string|null $alias
	 * @param int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function toggle(?string $alias = null, ?int $id = null): ?Response {
		$this->request->allowMethod(['post']);

		[$model, $entity, $uid, $reaction] = $this->resolveReactionRequest($alias, $id, 'toggle');

		$result = $this->Reactions->toggle($model, (int)$entity->get('id'), $uid, $reaction);
		$counts = $this->Reactions->counts($model, (int)$entity->get('id'));

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
	 * @param int|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function delete(?int $id = null): ?Response {
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
	 * @param int|null $id
	 * @param string $action
	 *
	 * @return array{0: string, 1: \Cake\Datasource\EntityInterface, 2: int, 3: string}
	 */
	protected function resolveReactionRequest(?string $alias, ?int $id, string $action): array {
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

}
