<?php

namespace Reactions\Utility;

use Cake\Core\Configure;
use InvalidArgumentException;

class Config {

	/**
	 * @var string
	 */
	public const STRATEGY_CONTROLLER = 'controller';

	/**
	 * @var string
	 */
	public const STRATEGY_ACTION = 'action';

	/**
	 * @param string $strategy
	 *
	 * @return string
	 */
	public static function strategy(string $strategy): string {
		if (!in_array($strategy, [static::STRATEGY_ACTION, static::STRATEGY_CONTROLLER], true)) {
			throw new InvalidArgumentException('Invalid strategy ' . $strategy);
		}

		return $strategy;
	}

	/**
	 * @param string $model
	 *
	 * @return string|null
	 */
	public static function alias(string $model): ?string {
		$models = static::models();

		/** @var string[] $keys */
		$keys = array_keys($models, $model, true);

		return $keys ? array_shift($keys) : null;
	}

	/**
	 * @return array<string, string>
	 */
	public static function models(): array {
		return (array)Configure::read('Reactions.models');
	}

}
