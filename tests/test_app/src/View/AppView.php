<?php
declare(strict_types=1);

namespace TestApp\View;

use Cake\View\View;

/**
 * @property \Reactions\View\Helper\ReactionsHelper $Reactions
 * @property \TinyAuth\View\Helper\AuthUserHelper $AuthUser
 */
class AppView extends View {

	/**
	 * @return void
	 */
	public function initialize(): void {
		$this->loadHelper('Reactions.Reactions');
	}

}
