<?php
declare(strict_types=1);

namespace Reactions\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Log\Log;
use Closure;
use Throwable;

/**
 * @property \Reactions\Model\Table\ReactionsTable $Reactions
 * @method \Reactions\Model\Entity\Reaction[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ReactionsController extends AppController {

	/**
	 * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event
	 *
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		if ($this->components()->has('Authorization') && method_exists($this->components()->get('Authorization'), 'skipAuthorization')) {
			$this->components()->get('Authorization')->skipAuthorization();
		}

		$gate = Configure::read('Reactions.adminAccess');
		if (!($gate instanceof Closure)) {
			throw new ForbiddenException(__d(
				'reactions',
				'Reactions admin backend is not configured. Set Reactions.adminAccess to a Closure that returns true for permitted callers.',
			));
		}

		try {
			$allowed = $gate($this->request) === true;
		} catch (ForbiddenException $e) {
			throw $e;
		} catch (Throwable $e) {
			Log::warning(sprintf('Reactions.adminAccess threw %s: %s', $e::class, $e->getMessage()));

			throw new ForbiddenException(__d('reactions', 'Reactions admin access denied.'));
		}

		if (!$allowed) {
			throw new ForbiddenException(__d('reactions', 'Reactions admin access denied.'));
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		if ($this->request->is(['post', 'put'])) {
			$model = $this->request->getQuery('model');
			$allowed = array_keys((array)Configure::read('Reactions.models'));
			if (!is_string($model) || !in_array($model, $allowed, true)) {
				throw new BadRequestException('Invalid model');
			}
			$count = $this->Reactions->reset($model);
			$this->Flash->success(__d('reactions', 'The reactions have been reset for `{0}`, deleted: {1}.', $model, $count));

			return $this->redirect(['action' => 'index']);
		}

		$models = $this->Reactions->find()
			->select(['model', 'count' => $this->Reactions->find()->func()->count('*')])
			->where(['model IS NOT' => null])
			->groupBy('model')
			->find('list', ...['keyField' => 'model', 'valueField' => 'count'])
			->toArray();

		$this->set(compact('models'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function listing() {
		$query = $this->Reactions->find()
			->contain(['Users']);
		$reactions = $this->paginate($query);

		$this->set(compact('reactions'));
	}

	/**
	 * @param string|null $id
	 *
	 * @return \Cake\Http\Response|null
	 */
	public function delete(?string $id = null): ?Response {
		$this->request->allowMethod(['post', 'delete']);
		$reaction = $this->Reactions->get($id);
		if ($this->Reactions->delete($reaction)) {
			$this->Flash->success(__d('reactions', 'The reaction has been deleted.'));
		} else {
			$this->Flash->error(__d('reactions', 'The reaction could not be deleted. Please, try again.'));
		}

		return $this->redirect(['action' => 'index']);
	}

}
