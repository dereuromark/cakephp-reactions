<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Reactions\Model\Table\ReactionsTable;

class ReactionsTableTest extends TestCase {

	/**
	 * @var \Reactions\Model\Table\ReactionsTable
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
		$config = $this->getTableLocator()->exists('Reactions') ? [] : ['className' => ReactionsTable::class];
		$this->Reactions = $this->getTableLocator()->get('Reactions', $config);
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->Reactions);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testAdd(): void {
		$result = $this->Reactions->add('Posts', 1, 1, '👍');

		$this->assertFalse($result->isNew());
		$this->assertSame(2, $this->Reactions->find()->count());

		$result = $this->Reactions->add('Posts', 1, 1, '❤️');
		$this->assertFalse($result->isNew());
		$this->assertSame(3, $this->Reactions->find()->count());
	}

	/**
	 * @return void
	 */
	public function testRemove(): void {
		$result = $this->Reactions->remove('Posts', 1, 1, '👍');

		$this->assertSame(1, $result);
		$this->assertSame(1, $this->Reactions->find()->count());
	}

	/**
	 * @return void
	 */
	public function testToggle(): void {
		$result = $this->Reactions->toggle('Posts', 1, 1, '👍');
		$this->assertSame('removed', $result);

		$result = $this->Reactions->toggle('Posts', 1, 1, '👍');
		$this->assertSame('added', $result);
	}

	/**
	 * @return void
	 */
	public function testCounts(): void {
		$this->Reactions->add('Posts', 1, 1, '❤️');
		$this->Reactions->add('Posts', 1, 2, '❤️');

		$result = $this->Reactions->counts('Posts', 1);
		ksort($result);

		$this->assertSame(['❤️' => 2, '🎉' => 1, '👍' => 1], $result);
	}

	/**
	 * @return void
	 */
	public function testReset(): void {
		$result = $this->Reactions->reset('Posts');

		$this->assertSame(2, $result);
		$this->assertSame(0, $this->Reactions->find()->count());
	}

}
