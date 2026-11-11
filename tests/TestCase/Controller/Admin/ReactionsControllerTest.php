<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Controller\Admin;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
	 * CakePHP's CspMiddleware stores the nonce as `cspScriptNonce`.
	 *
	 * @return void
	 */
	public function testIndexUsesCspScriptNonce(): void {
		$this->withRequestAttribute('cspScriptNonce', 'core-nonce');

		$this->get(['prefix' => 'Admin', 'plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertResponseContains('<script nonce="core-nonce">');
		$this->assertResponseNotRegExp('/<script(?![^>]*nonce=)/');
	}

	/**
	 * A custom middleware may still provide the nonce as `cspNonce`.
	 *
	 * @return void
	 */
	public function testListingFallsBackToCspNonce(): void {
		$this->withRequestAttribute('cspNonce', 'custom-nonce');

		$this->get(['prefix' => 'Admin', 'plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => 'listing']);

		$this->assertResponseOk();
		$this->assertResponseContains('<script nonce="custom-nonce">');
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

	/**
	 * Adds a middleware that sets a request attribute, like a CSP middleware would.
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return void
	 */
	protected function withRequestAttribute(string $name, string $value): void {
		EventManager::instance()->on('Server.buildMiddleware', function (EventInterface $event, MiddlewareQueue $queue) use ($name, $value): void {
			$queue->prepend(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($name, $value): ResponseInterface {
				return $handler->handle($request->withAttribute($name, $value));
			});
		});
	}

}
