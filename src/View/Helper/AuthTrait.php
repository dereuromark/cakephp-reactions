<?php

namespace Reactions\View\Helper;

use Cake\Core\Configure;

/**
 * @mixin \Cake\View\Helper
 */
trait AuthTrait {

	/**
	 * @return int|null
	 */
	protected function userId(): ?int {
		$userIdField = Configure::read('Reactions.userIdField') ?: 'id';
		$sessionKey = Configure::read('Reactions.sessionKey') ?? 'Auth.User';

		$uid = Configure::read($sessionKey . '.' . $userIdField);
		if ($uid) {
			return (int)$uid;
		}

		/** @var \App\View\AppView $view */
		$view = $this->_View;
		if ($view->helpers()->has('AuthUser')) {
			return $view->AuthUser->user($userIdField);
		}

		return $view->getRequest()->getSession()->read($sessionKey . '.' . $userIdField);
	}

}
