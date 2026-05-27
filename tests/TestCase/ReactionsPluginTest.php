<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase;

use Cake\Console\CommandCollection;
use Cake\TestSuite\TestCase;
use Reactions\ReactionsPlugin;

class ReactionsPluginTest extends TestCase {

	/**
	 * @return void
	 */
	public function testConsoleReturnsCommandCollection(): void {
		$commands = new CommandCollection();
		$plugin = new ReactionsPlugin();

		$this->assertSame($commands, $plugin->console($commands));
	}

}
