<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Reactions\Utility\Config;
use Reactions\View\Helper\ReactionsHelper;
use ReflectionMethod;

class ReactionsHelperTest extends TestCase {

	/**
	 * @var \Reactions\View\Helper\ReactionsHelper
	 */
	protected $Reactions;

	/**
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Reactions.Reactions',
		'plugin.Reactions.Users',
	];

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Configure::write('Auth.User.id', 1);
		$view = new View();
		$this->Reactions = new ReactionsHelper($view);

		Configure::write('Reactions.models.Posts', 'Posts');
		$this->loadRoutes();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->Reactions);

		parent::tearDown();

		Configure::delete('Reactions.models.Posts');
		Configure::delete('Auth.User.id');
	}

	/**
	 * @return void
	 */
	public function testWidget(): void {
		$result = $this->Reactions->widget('Posts', 1);

		$this->assertStringContainsString('Add reaction', $result);
		$this->assertStringContainsString('👍', $result);
	}

	/**
	 * Action strategy must post the actual reaction key in the `reaction` field,
	 * since ReactableComponent::process() reads it from there.
	 *
	 * @return void
	 */
	public function testActionStrategyDataCarriesReactionKey(): void {
		$this->Reactions->setConfig('strategy', Config::STRATEGY_ACTION);
		$method = new ReflectionMethod($this->Reactions, 'data');

		/** @var array<string, mixed> $data */
		$data = $method->invoke($this->Reactions, 'toggle', 'Posts', 1, '👍');

		$this->assertSame('👍', $data['reaction']);
		$this->assertSame('toggle', $data['action']);
		$this->assertSame('Posts', $data['alias']);
	}

	/**
	 * @return void
	 */
	public function testCounts(): void {
		$result = $this->Reactions->counts('Posts', 1);

		$this->assertStringContainsString('👍', $result);
		$this->assertStringContainsString('1', $result);
	}

	/**
	 * @return void
	 */
	public function testUrlToggle(): void {
		$result = $this->Reactions->urlToggle('Posts', 1);

		$this->assertSame([
			'plugin' => 'Reactions',
			'controller' => 'Reactions',
			'action' => 'toggle',
			'Posts',
			1,
		], $result);
	}

}
