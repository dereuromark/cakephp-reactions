<?php
declare(strict_types=1);

namespace Reactions\Test\TestCase\Model\Behavior;

use Cake\Http\Exception\BadRequestException;
use Cake\TestSuite\TestCase;
use Reactions\Model\Entity\Reaction;
use Reactions\Reaction as ReactionEnum;

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
	public function testFindReactedByReturnsRecordsForUser(): void {
		$posts = $this->table->find('reactedBy', userId: 1)->all()->toList();

		$this->assertCount(1, $posts);
		$this->assertSame(1, $posts[0]->get('id'));
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
	public function testCustomUserModelConfigUsesComputedAlias(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'userModelClass' => 'Accounts',
			'userModelConfig' => [
				'className' => 'Users',
				'foreignKey' => 'user_id',
			],
		]);

		$result = $this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 2,
			'userId' => 1,
			'reaction' => '🚀',
		]);

		$this->assertIsInt($result);
	}

	/**
	 * @return void
	 */
	public function testCounterCacheRefreshesOnAdd(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'counterCache' => true,
			'fieldCounter' => 'count',
		]);

		$this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 1,
			'userId' => 1,
			'reaction' => '❤️',
		]);

		$post = $this->table->get(1);
		$this->assertSame(3, $post->get('count'));
	}

	/**
	 * @return void
	 */
	public function testCounterCacheRefreshesOnRemove(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'counterCache' => true,
			'fieldCounter' => 'count',
		]);

		$this->table->getBehavior('Reactable')->removeReaction([
			'modelId' => 1,
			'userId' => 1,
			'reaction' => '👍',
		]);

		$post = $this->table->get(1);
		$this->assertSame(1, $post->get('count'));
	}

	/**
	 * @return void
	 */
	public function testCounterCacheRefreshesOnToggle(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'counterCache' => true,
			'fieldCounter' => 'count',
		]);

		// toggle removes existing 👍 → count drops to 1
		$this->table->getBehavior('Reactable')->toggleReaction([
			'modelId' => 1,
			'userId' => 1,
			'reaction' => '👍',
		]);
		$this->assertSame(1, $this->table->get(1)->get('count'));

		// toggle adds it back → count rises to 2
		$this->table->getBehavior('Reactable')->toggleReaction([
			'modelId' => 1,
			'userId' => 1,
			'reaction' => '👍',
		]);
		$this->assertSame(2, $this->table->get(1)->get('count'));
	}

	/**
	 * @return void
	 */
	public function testCounterCacheDisabledByDefault(): void {
		$this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 1,
			'userId' => 1,
			'reaction' => '❤️',
		]);

		$post = $this->table->get(1);
		$this->assertSame(0, $post->get('count'));
	}

	/**
	 * @return void
	 */
	public function testRefreshReactionCountUsesGivenModelString(): void {
		// refreshReactionCount must respect the caller-supplied model string so
		// counter cache works for configs that store a non-alias value (e.g.
		// `Reactions.models.Posts => 'Blog.Posts'`), not just $this->_table's alias.
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'counterCache' => true,
			'fieldCounter' => 'count',
		]);

		// Seed an extra row stored under a non-alias model string.
		$reactions = $this->table->Reactions->getTarget();
		$reactions->add('Blog.Posts', 1, 2, '🚀');

		$this->table->getBehavior('Reactable')->refreshReactionCount('Blog.Posts', 1);

		$this->assertSame(1, $this->table->get(1)->get('count'));
	}

	/**
	 * @return void
	 */
	public function testCounterCacheSilentWhenColumnMissing(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'counterCache' => true,
			'fieldCounter' => 'reactions_count',
		]);

		// `reactions_count` does not exist on the Posts fixture; call must not throw.
		$this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 1,
			'userId' => 1,
			'reaction' => '❤️',
		]);

		$this->assertSame(3, $this->table->Reactions->find()->count());
	}

	/**
	 * @return void
	 */
	public function testReactMethodAcceptsBackedEnum(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$result = $behavior->react(1, by: 2, with: ReactionEnum::Heart);

		$this->assertNotNull($result);
		$row = $this->table->Reactions->get($result);
		$this->assertSame('❤️', $row->reaction);
		$this->assertSame(2, $row->user_id);
	}

	/**
	 * @return void
	 */
	public function testUnreactMethodAcceptsBackedEnum(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$deleted = $behavior->unreact(1, by: 1, with: ReactionEnum::ThumbsUp);

		$this->assertSame(1, $deleted);
	}

	/**
	 * @return void
	 */
	public function testToggleMethodAcceptsBackedEnum(): void {
		$behavior = $this->table->getBehavior('Reactable');
		$removed = $behavior->toggle(1, by: 1, with: ReactionEnum::ThumbsUp);
		$added = $behavior->toggle(1, by: 1, with: ReactionEnum::ThumbsUp);

		$this->assertSame('removed', $removed['action']);
		$this->assertSame('added', $added['action']);
	}

	/**
	 * @return void
	 */
	public function testAddReactionAcceptsBackedEnumInArrayForm(): void {
		$id = $this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 1,
			'userId' => 2,
			'reaction' => ReactionEnum::Rocket,
		]);

		$this->assertNotNull($id);
		$this->assertSame('🚀', $this->table->Reactions->get($id)->reaction);
	}

	/**
	 * @return void
	 */
	public function testAllowedListMatchesEnumAndStringEntries(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'allowed' => [ReactionEnum::Heart, '🚀'],
		]);

		// enum entry matches string input
		$id = $this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 1,
			'userId' => 2,
			'reaction' => '❤️',
		]);
		$this->assertNotNull($id);

		// string entry matches enum input
		$id = $this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 1,
			'userId' => 2,
			'reaction' => ReactionEnum::Rocket,
		]);
		$this->assertNotNull($id);

		// reaction not in either form is rejected
		$this->expectException(BadRequestException::class);
		$this->table->getBehavior('Reactable')->addReaction([
			'modelId' => 1,
			'userId' => 2,
			'reaction' => ReactionEnum::ThumbsDown,
		]);
	}

	/**
	 * @return void
	 */
	public function testIsReactionAllowed(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		$this->table->addBehavior('Reactions.Reactable', [
			'allowed' => [ReactionEnum::Heart, '🚀'],
		]);

		$behavior = $this->table->getBehavior('Reactable');

		$this->assertTrue($behavior->isReactionAllowed('❤️'));
		$this->assertTrue($behavior->isReactionAllowed(ReactionEnum::Rocket));
		$this->assertFalse($behavior->isReactionAllowed('👍'));
		$this->assertFalse($behavior->isReactionAllowed(''));
	}

	/**
	 * @return void
	 */
	public function testReactableLoadsAlongsideBehaviorExposingToggleMethod(): void {
		$this->getTableLocator()->clear();
		$this->table = $this->getTableLocator()->get('Posts');
		// Test-only behavior that also exposes a `toggle()` method must not collide.
		$this->table->addBehavior('ToggleStub');
		$this->table->addBehavior('Reactions.Reactable');

		$this->assertTrue($this->table->behaviors()->has('Reactable'));
		$this->assertSame('stub', $this->table->getBehavior('ToggleStub')->toggle());
		$result = $this->table->getBehavior('Reactable')->toggle(1, by: 1, with: ReactionEnum::ThumbsUp);
		$this->assertSame('removed', $result['action']);
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
