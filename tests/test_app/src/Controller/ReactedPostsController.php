<?php

namespace TestApp\Controller;

use Cake\Controller\Controller;

class ReactedPostsController extends Controller {

	protected ?string $defaultTable = 'Posts';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
		$this->loadComponent('Reactions.Reactable');
	}

	/**
	 * @param int|null $id
	 *
	 * @return void
	 */
	public function view($id = null) {
		$post = $this->fetchTable('Posts')->get($id, contain: ['Reactions']);

		$this->set('post', $post);
	}

}
