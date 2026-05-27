<?php
declare(strict_types=1);

namespace TestApp\Model\Behavior;

use Cake\ORM\Behavior;

/**
 * Test-only behavior that exposes a `toggle()` table magic method so the suite
 * can prove `ReactableBehavior` does not collide on shared verb names.
 */
class ToggleStubBehavior extends Behavior {

	/**
	 * @return string
	 */
	public function toggle(): string {
		return 'stub';
	}

}
