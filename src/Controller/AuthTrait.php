<?php

namespace Reactions\Controller;

use Cake\Core\Configure;

/**
 * @mixin \App\Controller\AppController
 */
trait AuthTrait {

	/**
	 * @return int|null
	 */
	protected function userId() {
		$userIdField = Configure::read('Reactions.userIdField') ?: 'id';
		$sessionKey = Configure::read('Reactions.sessionKey') ?? 'Auth.User';

		$uid = Configure::read($sessionKey . '.' . $userIdField);
		if ($uid) {
			return $uid;
		}

		return $this->getRequest()->getSession()->read($sessionKey . '.' . $userIdField);
	}

}
