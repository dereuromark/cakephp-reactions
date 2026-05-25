# Reaction type

`ReactableBehavior` allows multiple distinct reactions per user on the same record.
A user can react with `👍` and `🎉` to one post, but cannot store `👍` twice for that post.

## Quick Setup

```php
// In your Table class
$this->addBehavior('Reactions.Reactable');
```

```php
$this->Articles->addReaction([
	'modelId' => $articleId,
	'userId' => $userId,
	'reaction' => 'thumbsup',
]);
```

```php
$this->Articles->toggleReaction([
	'modelId' => $articleId,
	'userId' => $userId,
	'reaction' => 'thumbsup',
]);
```

## Allowed Reactions

Set `allowed` to `null` to accept any reaction key, or provide an allow-list:

```php
$this->addBehavior('Reactions.Reactable', [
	'allowed' => ['👍', '👎', '❤️', 'rocket'],
]);
```

Invalid reactions raise `BadRequestException`.

## Finders

```php
$article = $this->Articles->find('reactions', id: $articleId)->firstOrFail();
$myArticles = $this->Articles->find('reactedBy', userId: $userId)->all();
```

## Helper

```php
echo $this->Reactions->widget('Posts', $post->id);
echo $this->Reactions->counts('Posts', $post->id);
```
