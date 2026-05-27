# Reaction API

`ReactableBehavior` lets a model receive multiple named or emoji reactions.

Examples:

- one user can add `👍` and `🎉` to the same post,
- the same user cannot add `👍` twice to that post,
- another user can also add `👍` to that post.

The uniqueness rule is:

```text
model + foreign_key + user_id + reaction
```

## Behavior Setup

```php
// in src/Model/Table/PostsTable.php
public function initialize(array $config): void {
	parent::initialize($config);

	$this->addBehavior('Reactions.Reactable');
}
```

With common options:

```php
$this->addBehavior('Reactions.Reactable', [
	'allowed' => ['👍', '👎', '❤️', '🎉', '🚀'],
	'counterCache' => true,
	'fieldCounter' => 'reactions_count',
]);
```

## Add A Reaction

```php
$reactionId = $this->Posts->addReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);
```

Returns:

- `int` when a new reaction row was created,
- `null` when the reaction already existed.

## Remove A Reaction

```php
$deleted = $this->Posts->removeReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);
```

Returns the number of deleted rows.

## Toggle A Reaction

```php
$result = $this->Posts->toggleReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);
```

Returns:

```php
[
	'action' => 'added', // or 'removed'
	'counts' => [
		'👍' => 2,
		'🎉' => 1,
	],
]
```

## Counts

```php
$counts = $this->Posts->reactionCounts($postId);
```

Returns an array keyed by reaction:

```php
[
	'👍' => 2,
	'🎉' => 1,
]
```

Counts are sorted by reaction key for deterministic output.

## Current User Reactions

```php
$reactions = $this->Posts->userReactions($postId, $userId);
```

Returns the reaction keys that user has stored for the record:

```php
['👍', '🎉']
```

## Finders

Contain reactions and users for one record:

```php
$post = $this->Posts
	->find('reactions', id: $postId)
	->firstOrFail();
```

Find records reacted to by a user:

```php
$posts = $this->Posts
	->find('reactedBy', userId: $userId)
	->all();
```

## Allowed Reactions

Set `allowed` to `null` to accept any non-empty reaction key:

```php
$this->addBehavior('Reactions.Reactable', [
	'allowed' => null,
]);
```

Set an array to restrict accepted keys:

```php
$this->addBehavior('Reactions.Reactable', [
	'allowed' => ['👍', '👎', '❤️', 'rocket'],
]);
```

Invalid keys raise `BadRequestException`.

Controller endpoints also check `allowed`: they use the host table's loaded
behavior config when available, otherwise `Configure::read('Reactions.allowed')`.

## Model Override

Most callers should let the behavior use the host table alias as the stored
model string. If you need to write rows for another stored model string, pass
`model` explicitly:

```php
$this->Posts->addReaction([
	'model' => 'Blog.Posts',
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);
```

Counter cache updates are skipped when the explicit `model` does not match the
behavior's configured model, because that counter belongs to the host table.

## Helper Example

```php
echo $this->Reactions->widget('Posts', $post->id);
echo $this->Reactions->counts('Posts', $post->id);
```

The helper reads the current user's reactions so it can mark selected reaction
buttons with the `active` class suffix.
