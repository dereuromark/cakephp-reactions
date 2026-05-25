<?php

declare(strict_types=1);

namespace Reactions\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Reactions\Utility\Config;

class ConfigTest extends TestCase {

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Configure::delete('Reactions.models');

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testStrategyConstants(): void {
		$this->assertSame('controller', Config::STRATEGY_CONTROLLER);
		$this->assertSame('action', Config::STRATEGY_ACTION);
	}

	/**
	 * @return void
	 */
	public function testStrategyWithController(): void {
		$this->assertSame('controller', Config::strategy(Config::STRATEGY_CONTROLLER));
	}

	/**
	 * @return void
	 */
	public function testStrategyWithAction(): void {
		$this->assertSame('action', Config::strategy(Config::STRATEGY_ACTION));
	}

	/**
	 * @return void
	 */
	public function testStrategyWithInvalidValue(): void {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid strategy invalid');

		Config::strategy('invalid');
	}

	/**
	 * @return void
	 */
	public function testAlias(): void {
		Configure::write('Reactions.models', [
			'Posts' => 'Blog.Posts',
			'Articles' => 'App.Articles',
		]);

		$this->assertSame('Posts', Config::alias('Blog.Posts'));
		$this->assertSame('Articles', Config::alias('App.Articles'));
	}

	/**
	 * @return void
	 */
	public function testAliasNotFound(): void {
		Configure::write('Reactions.models', [
			'Posts' => 'Blog.Posts',
		]);

		$this->assertNull(Config::alias('NonExistent'));
	}

	/**
	 * @return void
	 */
	public function testModels(): void {
		Configure::write('Reactions.models', [
			'Posts' => 'Blog.Posts',
			'Articles' => 'App.Articles',
		]);

		$result = Config::models();
		$this->assertArrayHasKey('Posts', $result);
		$this->assertArrayHasKey('Articles', $result);
	}

	/**
	 * @return void
	 */
	public function testModelsEmpty(): void {
		Configure::delete('Reactions.models');

		$result = Config::models();
		$this->assertIsArray($result);
	}

}
