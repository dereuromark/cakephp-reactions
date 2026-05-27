<?php

namespace Reactions\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Utility\Inflector;

/**
 * @property \Cake\Controller\Component\FlashComponent $Flash
 *
 * @method \App\Controller\AppController getController()
 */
class ReactableComponent extends Component {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'on' => 'startup',
		'userIdField' => 'id',
		'sessionKey' => null,
		'useEntity' => false,
		'actions' => ['view', 'reactions'],
	];

	/**
	 * @var array
	 */
	protected array $components = [
		'Flash',
	];

	/**
	 * @var \App\Controller\AppController
	 */
	protected $Controller;

	/**
	 * @var string|null
	 */
	protected ?string $modelAlias = null;

	/**
	 * @var string|null
	 */
	protected ?string $viewVariable = null;

	/**
	 * @param array $config
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		$this->Controller = $this->getController();

		$config += (array)Configure::read('Reactions');
		$this->setConfig($config);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function startup(EventInterface $event): void {
		if (!$this->isConfiguredAction()) {
			return;
		}

		$model = $this->Controller->fetchTable();
		$this->modelAlias = $model->getAlias();
		$parts = explode('\\', $model->getEntityClass());
		$entityName = Inflector::classify(Inflector::underscore((string)array_pop($parts)));
		$this->viewVariable = Inflector::variable($entityName);

		if (!$this->Controller->{$this->modelAlias}->behaviors()->has('Reactable')) {
			$this->Controller->{$this->modelAlias}->behaviors()->load('Reactions.Reactable', [
				'userModelClass' => $this->getConfig('userModelClass') ?: 'Users',
				'userId' => $this->userId(),
			]);
		}

		if (!$this->Controller->getRequest()->is(['post', 'put', 'patch'])) {
			return;
		}
		if ($this->getConfig('on') !== 'startup') {
			return;
		}

		$result = $this->process();
		if ($result !== null) {
			$event->setResult($result);
		}
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 *
	 * @return void
	 */
	public function beforeRender(EventInterface $event): void {
		if (!$this->isConfiguredAction()) {
			return;
		}
		if ($this->getConfig('on') !== 'beforeRender') {
			return;
		}

		$result = $this->process();
		if ($result !== null) {
			$event->setResult($result);
		}
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	protected function process() {
		$data = $this->Controller->getRequest()->getData();
		if (empty($data['reaction'])) {
			return null;
		}

		$alias = $data['alias'] ?? null;
		$id = $data['id'] ?? null;
		$reaction = $data['reaction'] ?? null;
		$action = $data['action'] ?? null;
		if (!is_string($alias) || $alias === '' || !is_scalar($id) || !is_string($reaction) || !is_string($action)) {
			throw new BadRequestException('Missing reaction payload');
		}

		if ($this->getConfig('useEntity')) {
			if ($this->getConfig('on') === 'startup') {
				throw new BadRequestException(
					'useEntity requires `on` to be `beforeRender`; the controller action has not yet set the view variable during startup.',
				);
			}
			$entity = $this->Controller->viewBuilder()->getVar((string)$this->viewVariable);
			if (!$entity) {
				throw new BadRequestException('Missing reaction payload');
			}
			$id = $entity->get('id');
		}

		if (!in_array($action, ['add', 'remove', 'toggle'], true)) {
			throw new BadRequestException('Invalid reaction action');
		}

		$userId = $this->userId();
		if (!$userId) {
			throw new MethodNotAllowedException('Must be logged in to react');
		}

		$options = [
			'model' => $alias,
			'modelId' => $id,
			'userId' => $userId,
			'reaction' => $reaction,
		];

		/** @var \Reactions\Model\Behavior\ReactableBehavior $behavior */
		$behavior = $this->Controller->{$this->modelAlias}->getBehavior('Reactable');
		$result = match ($action) {
			'add' => $behavior->addReaction($options),
			'remove' => $behavior->removeReaction($options),
			'toggle' => $behavior->toggleReaction($options),
		};

		if ($this->Controller->getRequest()->is('ajax') || $this->Controller->getRequest()->is('json')) {
			$this->Controller->set('reactionResult', $result);

			return null;
		}

		return $this->Controller->redirect($this->Controller->referer(['action' => 'index']));
	}

	/**
	 * @return bool
	 */
	protected function isConfiguredAction(): bool {
		$actions = $this->getConfig('actions');
		if (!$actions) {
			return true;
		}

		$action = $this->Controller->getRequest()->getParam('action') ?: '';

		return in_array($action, $actions, true);
	}

	/**
	 * @return int|null
	 */
	protected function userId(): ?int {
		$userIdField = Configure::read('Reactions.userIdField') ?: 'id';
		$sessionKey = $this->getConfig('sessionKey') ?? Configure::read('Reactions.sessionKey') ?? 'Auth.User';

		$uid = Configure::read($sessionKey . '.' . $userIdField);
		if ($uid) {
			return (int)$uid;
		}

		$userId = $this->getConfig('userId') ?: null;
		if (!$userId) {
			$userId = $this->Controller->getRequest()->getSession()->read($sessionKey . '.' . $userIdField);
		}

		return $userId !== null ? (int)$userId : null;
	}

}
