<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ReactionsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Reactions.Reactions',
		'plugin.Reactions.Posts',
		'plugin.Reactions.Users',
	];

	/**
	 * @return void
	 */
	public function testToggle(): void {
		Configure::write('Reactions.models.Posts', 'Posts');

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'toggle', 'Posts', 1], ['reaction' => '👍']);

		$this->assertRedirect(['action' => 'index']);

		Configure::delete('Reactions.models');
	}

	/**
	 * @return void
	 */
	public function testToggleJson(): void {
		Configure::write('Reactions.models.Posts', 'Posts');

		$this->configRequest([
			'headers' => [
				'Accept' => 'application/json',
				'X-Requested-With' => 'XMLHttpRequest',
			],
		]);
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'toggle', 'Posts', 1], ['reaction' => '👍']);

		$this->assertResponseOk();
		$this->assertContentType('application/json');
		$this->assertStringContainsString('"action": "removed"', (string)$this->_response->getBody());

		Configure::delete('Reactions.models');
	}

}
