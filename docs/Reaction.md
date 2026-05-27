# Reaction type

`ReactableBehavior` allows multiple distinct reactions per user on the same record.
A user can react with `👍` and `🎉` to one post, but cannot store `👍` twice for that post.

## Quick Setup

```php
// In your Table class
$this->addBehavior('Reactions.Reactable');
```

```php
use Reactions\Reaction;

$behavior = $this->Articles->getBehavior('Reactable');

$behavior->react($articleId, by: $userId, with: Reaction::ThumbsUp);
$behavior->toggle($articleId, by: $userId, with: Reaction::Rocket);
```

## Reaction type

The bundled `Reactions\Reaction` enum exposes the eight default emoji as cases (`ThumbsUp`, `ThumbsDown`, `Laugh`, `Confused`, `Heart`, `Party`, `Rocket`, `Eyes`). Apps that want their own reaction set should declare their own string-backed enum; the behavior and controller accept any `string|\BackedEnum` so the plugin remains open to custom keys.

`react()`, `unreact()`, and `toggle()` are short, typed aliases for the array-form methods `addReaction()` / `removeReaction()` / `toggleReaction()`, which are still supported and now also accept BackedEnum cases in the `reaction` slot:

```php
$behavior->addReaction([
	'modelId' => $articleId,
	'userId' => $userId,
	'reaction' => Reaction::ThumbsUp,
]);
```

> [!NOTE]
> `toggle()` is a generic verb on `ReactableBehavior` — if your host table already exposes a `toggle()` method, prefer the array-form `toggleReaction()` to avoid a collision.

## Allowed Reactions

Set `allowed` to `null` to accept any reaction key, or provide an allow-list. Entries may be strings, BackedEnum cases, or a mix:

```php
$this->addBehavior('Reactions.Reactable', [
	'allowed' => [Reaction::ThumbsUp, Reaction::ThumbsDown, Reaction::Heart, 'rocket'],
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
