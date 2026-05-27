<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ReactionsControllerTest extends TestCase {

	use IntegrationTestTrait;

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

		$this->loadRoutes();
		Configure::write('Reactions.adminAccess', function (ServerRequest $request): bool {
			return true;
		});
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Configure::delete('Reactions.adminAccess');
		Configure::delete('Reactions.models');

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testIndex(): void {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertResponseContains('Posts');
	}

	/**
	 * @return void
	 */
	public function testIndexResetModel(): void {
		Configure::write('Reactions.models.Posts', 'Posts');

		$this->post(['prefix' => 'Admin', 'plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'index', '?' => ['model' => 'Posts']]);

		$this->assertRedirect(['action' => 'index']);
		$this->assertSame(0, $this->fetchTable('Reactions.Reactions')->find()->count());
	}

	/**
	 * @return void
	 */
	public function testListing(): void {
		$this->get(['prefix' => 'Admin', 'plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'listing']);

		$this->assertResponseOk();
		$this->assertResponseContains('Posts');
	}

	/**
	 * @return void
	 */
	public function testDelete(): void {
		$this->post(['prefix' => 'Admin', 'plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'delete', 1]);

		$this->assertRedirect(['action' => 'index']);
		$this->assertSame(1, $this->fetchTable('Reactions.Reactions')->find()->count());
	}

}
