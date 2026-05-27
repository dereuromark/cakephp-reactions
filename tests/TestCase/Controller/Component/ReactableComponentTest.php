<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Controller\Component;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ReactableComponentTest extends TestCase {

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
		Configure::delete('Reactions.on');

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testToggleReactionOnHostAction(): void {
		$this->loadRoutes();
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->post(['controller' => 'ReactedPosts', 'action' => 'view', 1], [
			'action' => 'toggle',
			'alias' => 'Posts',
			'id' => 1,
			'reaction' => '❤️',
		]);

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
	public function testToggleReactionOnHostActionBeforeRender(): void {
		Configure::write('Reactions.on', 'beforeRender');
		$this->loadRoutes();
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->post(['controller' => 'ReactedPosts', 'action' => 'view', 1], [
			'action' => 'toggle',
			'alias' => 'Posts',
			'id' => 1,
			'reaction' => '❤️',
		]);

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

}
