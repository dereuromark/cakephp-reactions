<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
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
	protected function tearDown(): void {
		parent::tearDown();

		Configure::delete('Reactions.allowed');
		Configure::delete('Reactions.models');
	}

	/**
	 * @return void
	 */
	public function testAdd(): void {
		Configure::write('Reactions.models.Posts', 'Posts');
		$this->login();

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'add', 'Posts', 1], ['reaction' => '❤️']);

		$this->assertRedirect(['action' => 'index']);

		$count = $this->fetchTable('Reactions.Reactions')->find()
			->where([
				'model' => 'Posts',
				'foreign_key' => 1,
				'user_id' => 1,
				'reaction' => '❤️',
			])
			->count();
		$this->assertSame(1, $count);
	}

	/**
	 * @return void
	 */
	public function testRemove(): void {
		Configure::write('Reactions.models.Posts', 'Posts');
		$this->login();

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'remove', 'Posts', 1], ['reaction' => '👍']);

		$this->assertRedirect(['action' => 'index']);

		$count = $this->fetchTable('Reactions.Reactions')->find()
			->where([
				'model' => 'Posts',
				'foreign_key' => 1,
				'user_id' => 1,
				'reaction' => '👍',
			])
			->count();
		$this->assertSame(0, $count);
	}

	/**
	 * @return void
	 */
	public function testToggle(): void {
		Configure::write('Reactions.models.Posts', 'Posts');
		$this->login();

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'toggle', 'Posts', 1], ['reaction' => '👍']);

		$this->assertRedirect(['action' => 'index']);
	}

	/**
	 * @return void
	 */
	public function testToggleRefreshesCounterCacheOnHostTable(): void {
		Configure::write('Reactions.models.Posts', 'Posts');

		$posts = $this->fetchTable('Posts');
		$posts->addBehavior('Reactions.Reactable', [
			'counterCache' => true,
			'fieldCounter' => 'count',
		]);
		$this->login();

		// Fixture has 2 reactions for Posts/1; toggle removes one → counter = 1.
		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'toggle', 'Posts', 1], ['reaction' => '👍']);
		$this->assertSame(1, $posts->get(1)->get('count'));
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
		$this->login();

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'toggle', 'Posts', 1], ['reaction' => '👍']);

		$this->assertResponseOk();
		$this->assertContentType('application/json');
		$this->assertStringContainsString('"action": "removed"', (string)$this->_response->getBody());
	}

	/**
	 * @return void
	 */
	public function testToggleRejectsDisallowedReaction(): void {
		Configure::write('Reactions.models.Posts', 'Posts');
		Configure::write('Reactions.allowed', ['👍']);
		$this->disableErrorHandlerMiddleware();
		$this->login();

		$this->expectException(BadRequestException::class);

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'toggle', 'Posts', 1], ['reaction' => '❤️']);
	}

	/**
	 * @return void
	 */
	public function testDelete(): void {
		$this->login();

		$this->post(['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'delete', 1]);

		$this->assertRedirect(['action' => 'index']);

		$count = $this->fetchTable('Reactions.Reactions')->find()
			->where(['id' => 1])
			->count();
		$this->assertSame(0, $count);
	}

	/**
	 * @return void
	 */
	protected function login(): void {
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);
	}

}
