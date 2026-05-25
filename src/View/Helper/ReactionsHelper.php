<?php

namespace Reactions\View\Helper;

use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\View\Helper;
use Reactions\Utility\Config;
use RuntimeException;

/**
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \Cake\View\Helper\FormHelper $Form
 */
class ReactionsHelper extends Helper {

	use ModelAwareTrait;
	use AuthTrait;

	/**
	 * @var array<string, string>
	 */
	public const ICONS_GITHUB = [
		'👍' => '👍',
		'👎' => '👎',
		'😄' => '😄',
		'😕' => '😕',
		'❤️' => '❤️',
		'🎉' => '🎉',
		'🚀' => '🚀',
		'👀' => '👀',
	];

	/**
	 * @var array
	 */
	protected array $helpers = [
		'Url',
		'Form',
	];

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'strategy' => Config::STRATEGY_CONTROLLER,
		'icons' => [],
		'html' => '<span class="reaction%s">%s</span>',
	];

	/**
	 * @param array<string, mixed> $config
	 *
	 * @return void
	 */
	public function initialize(array $config): void {
		$config += (array)Configure::read('Reactions');
		$this->setConfig($config);
	}

	/**
	 * @param string $alias
	 * @param string|int $id
	 *
	 * @return string
	 */
	public function widget(string $alias, int|string $id): string {
		$userReactions = $this->userReactionMap($alias, $id);
		$icons = $this->icons();
		$selected = [];
		$picker = [];

		foreach ($icons as $reaction => $display) {
			$html = $this->iconHtml($display, isset($userReactions[$reaction]) ? ' active' : '');
			$picker[] = $this->Form->postLink($html, $this->url('toggle', $alias, $id), [
				'escapeTitle' => false,
				'block' => true,
				'data' => $this->data('toggle', $alias, $id, $reaction),
			]);

			if (isset($userReactions[$reaction])) {
				$selected[] = $html;
			}
		}

		$current = $selected ? implode(' ', $selected) . ' ' : '';

		return $current . '<details class="reaction-buttons"><summary>' . __d('reactions', 'Add reaction') . '</summary>' . implode(' ', $picker) . '</details>';
	}

	/**
	 * @param string $alias
	 * @param string|int $id
	 *
	 * @return string
	 */
	public function counts(string $alias, int|string $id): string {
		$table = $this->reactionTable();
		$counts = $table->counts($alias, (int)$id);
		$icons = $this->icons();
		$out = [];

		foreach ($counts as $reaction => $count) {
			$display = $icons[$reaction] ?? $reaction;
			$out[] = $this->iconHtml($display) . ' ' . $count;
		}

		return implode(' ', $out);
	}

	/**
	 * @param string $alias
	 * @param string|int $id
	 *
	 * @return array|string
	 */
	public function urlToggle(string $alias, int|string $id): string|array {
		return $this->url('toggle', $alias, $id);
	}

	/**
	 * @param string $action
	 * @param string $alias
	 * @param string|int $id
	 * @param string $reaction
	 *
	 * @return array<string, mixed>
	 */
	protected function data(string $action, string $alias, int|string $id, string $reaction): array {
		$strategy = Config::strategy((string)$this->getConfig('strategy'));

		return match ($strategy) {
			Config::STRATEGY_ACTION => ['reaction' => $reaction, 'action' => $action, 'alias' => $alias, 'id' => $id],
			Config::STRATEGY_CONTROLLER => ['reaction' => $reaction],
			default => throw new BadMethodCallException('Not implemented: ' . $strategy),
		};
	}

	/**
	 * @param string $action
	 * @param string $alias
	 * @param string|int $id
	 *
	 * @return array|string
	 */
	protected function url(string $action, string $alias, int|string $id): string|array {
		$strategy = Config::strategy((string)$this->getConfig('strategy'));

		return match ($strategy) {
			Config::STRATEGY_ACTION => $this->_View->getRequest()->getUri()->getPath(),
			Config::STRATEGY_CONTROLLER => ['plugin' => 'Reactions', 'controller' => 'Reactions', 'action' => $action, $alias, $id],
			default => throw new BadMethodCallException('Not implemented: ' . $strategy),
		};
	}

	/**
	 * @return array<string, string>
	 */
	protected function icons(): array {
		/** @var array<string, string> $icons */
		$icons = $this->getConfig('icons') ?: [];
		if (!$icons) {
			$icons = static::ICONS_GITHUB;
		}

		if (!$icons) {
			throw new RuntimeException('Icons are not defined yet');
		}

		return $icons;
	}

	/**
	 * @param string $alias
	 * @param string|int $id
	 *
	 * @return array<string, bool>
	 */
	protected function userReactionMap(string $alias, int|string $id): array {
		$uid = $this->userId();
		if (!$uid) {
			throw new MethodNotAllowedException('Must be logged in');
		}

		$table = $this->reactionTable();
		$entities = $table->find()
			->select(['reaction'])
			->where([
				'model' => $alias,
				'foreign_key' => $id,
				'user_id' => $uid,
			])
			->enableHydration(false)
			->all()
			->extract('reaction')
			->toList();

		return array_fill_keys($entities, true);
	}

	/**
	 * @return \Reactions\Model\Table\ReactionsTable
	 */
	protected function reactionTable() {
		$class = $this->getConfig('reactionClass') ?: 'Reactions.Reactions';

		/** @var \Reactions\Model\Table\ReactionsTable */
		return $this->fetchModel($class);
	}

	/**
	 * @param string $display
	 * @param string $classSuffix
	 *
	 * @return string
	 */
	protected function iconHtml(string $display, string $classSuffix = ''): string {
		/** @var string $html */
		$html = $this->getConfig('html');

		return sprintf($html, $classSuffix, $display);
	}

}
