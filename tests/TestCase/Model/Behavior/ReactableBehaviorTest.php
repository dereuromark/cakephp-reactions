<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Model\Behavior;

use Cake\Http\Exception\BadRequestException;
use Cake\TestSuite\TestCase;
use Reactions\Model\Entity\Reaction;

class ReactableBehaviorTest extends TestCase {

	/**
	 * @var \Cake\ORM\Table&\Reactions\Model\Behavior\ReactableBehavior
	 */
	protected $table;

	/**
	 * @var list<string>
	 */
	protected array $fixtures = [
		'plugin.Reactions.Reactions',
		'plugin.Reactions.Users',
		'plugin.Reactions.Posts',
	];

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable');
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->table);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testAddReactionIdempotent(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$result = $behavior->addReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '👍']);
		$second = $behavior->addReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '👍']);

		$this->assertSame($result, $second);
		$this->assertSame(2, $this->table->Reactions->find()->count());
	}

	/**
	 * @return void
	 */
	public function testMultipleDifferentReactionsCanCoexist(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$behavior->addReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '❤️']);

		$reactions = $this->table->Reactions->find()->where(['user_id' => 1, 'foreign_key' => 1])->all()->toList();

		$this->assertCount(2, $reactions);
	}

	/**
	 * @return void
	 */
	public function testToggleReaction(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$removed = $behavior->toggleReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '👍']);
		$added = $behavior->toggleReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '👍']);

		$this->assertSame('removed', $removed['action']);
		$this->assertSame(['🎉' => 1], $removed['counts']);
		$this->assertSame('added', $added['action']);
		$this->assertSame(['🎉' => 1, '👍' => 1], $added['counts']);
	}

	/**
	 * @return void
	 */
	public function testRemoveReaction(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$result = $behavior->removeReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '👍']);

		$this->assertSame(1, $result);
	}

	/**
	 * @return void
	 */
	public function testReactionCounts(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$behavior->addReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '❤️']);
		$behavior->addReaction(['modelId' => 1, 'userId' => 2, 'reaction' => '❤️']);

		$result = $behavior->reactionCounts(1);
		ksort($result);

		$this->assertSame(['❤️' => 2, '🎉' => 1, '👍' => 1], $result);
	}

	/**
	 * @return void
	 */
	public function testUserReactions(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$behavior->addReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '❤️']);

		$result = $behavior->userReactions(1, 1);
		sort($result);

		$this->assertSame(['❤️', '👍'], $result);
	}

	/**
	 * @return void
	 */
	public function testFindReactionsReturnsRequestedRecord(): void {
		/** @var \Cake\Datasource\EntityInterface $post */
		$post = $this->table->find('reactions', id: 2)->firstOrFail();
		$this->assertSame(2, $post->get('id'));
		$this->assertSame([], $post->get('reactions'));

		/** @var \Cake\Datasource\EntityInterface $post */
		$post = $this->table->find('reactions', id: 1)->firstOrFail();
		$this->assertSame(1, $post->get('id'));
		$this->assertCount(2, $post->get('reactions'));
	}

	/**
	 * @return void
	 */
	public function testAllowedSetRejectsUnknownReaction(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', ['allowed' => ['👍']]);

		$this->expectException(BadRequestException::class);

		$this->table->getBehavior('Reactable')->addReaction(['modelId' => 1, 'userId' => 1, 'reaction' => '❤️']);
	}

	/**
	 * @return void
	 */
	public function testSecurityFieldsAreNotMassAssignable(): void {
		$entity = new Reaction();
		$entity = $entity->patch([
			'id' => 99,
			'user_id' => 9,
			'model' => 'Injected',
			'foreign_key' => 9,
			'reaction' => '👍',
			'created' => '2026-01-01 00:00:00',
		]);

		$this->assertNull($entity->id);
		$this->assertNull($entity->user_id);
		$this->assertNull($entity->model);
		$this->assertNull($entity->foreign_key);
		$this->assertSame('👍', $entity->reaction);
		$this->assertNull($entity->created);
	}

}
