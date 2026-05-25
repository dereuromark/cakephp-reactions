<?php

declare(strict_types=1);

namespace Reactions;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

class ReactionsPlugin extends BasePlugin {

	/**
	 * @param \Cake\Core\PluginApplicationInterface $app
	 *
	 * @return void
	 */
	public function bootstrap(PluginApplicationInterface $app): void {
	}

	/**
	 * @param \Cake\Routing\RouteBuilder $routes
	 *
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->plugin(
			'Reactions',
			function (RouteBuilder $builder) {
				$builder->fallbacks();
			},
		);

		$routes->prefix('Admin', function (RouteBuilder $builder) {
			$builder->plugin(
				'Reactions',
				function (RouteBuilder $builder) {
					$builder->connect('/', ['controller' => 'Reactions', 'action' => 'index']);

					$builder->fallbacks();
				},
			);
		});

		parent::routes($routes);
	}

	/**
	 * @param \Cake\Http\MiddlewareQueue $middlewareQueue
	 *
	 * @return \Cake\Http\MiddlewareQueue
	 */
	public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
		return $middlewareQueue;
	}

	/**
	 * @param \Cake\Console\CommandCollection $commands
	 *
	 * @return \Cake\Console\CommandCollection
	 */
	public function console(CommandCollection $commands): CommandCollection {
		return $commands;
	}

	/**
	 * @param \Cake\Core\ContainerInterface $container
	 *
	 * @return void
	 */
	public function services(ContainerInterface $container): void {
	}

}
