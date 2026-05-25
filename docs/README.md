# Reactions Plugin docs

## Install
```bash
composer require dereuromark/cakephp-reactions
```

Load the plugin and run the migration:

```bash
bin/cake plugin load Reactions
bin/cake migrations migrate -p Reactions
```

## Setup

Attach the behavior to every model you want to react to:

```php
$this->addBehavior('Reactions.Reactable');
```

## Usage

```php
$this->Articles->addReaction([
	'modelId' => $articleId,
	'userId' => $userId,
	'reaction' => '👍',
]);

$this->Articles->toggleReaction([
	'modelId' => $articleId,
	'userId' => $userId,
	'reaction' => 'rocket',
]);

$counts = $this->Articles->reactionCounts($articleId);
$userReactions = $this->Articles->userReactions($articleId, $userId);
```

Restrict the allowed set if you do not want arbitrary reaction keys:

```php
$this->addBehavior('Reactions.Reactable', [
	'allowed' => ['👍', '👎', '❤️', 'rocket'],
]);
```

## Helper

```php
// in AppView::initialize()
$this->loadHelper('Reactions.Reactions');
```

```php
echo $this->Reactions->widget('Posts', $post->id);
echo $this->Reactions->counts('Posts', $post->id);
```

> [!NOTE]
> The widget renders its buttons as `postLink()` forms with `block => true` so they are
> hoisted out of any surrounding `<form>` (nested forms are invalid HTML). Your layout must
> output that block once, e.g. `<?= $this->fetch('postLink') ?>` before `</body>`, otherwise
> the buttons render but submit nothing.

## Admin Backend

Go to `/admin/reactions`.

The backend is deny-by-default. Configure `Reactions.adminAccess` to a Closure that
returns literal `true` for allowed callers.

## Detailed Docs

- [Reaction](Reaction.md)

## Database Configuration

### Polymorphic foreign key type

The `reactions_reactions.foreign_key` column type is configurable via the global
`Polymorphic.type` key.

```php
'Polymorphic' => [
	'type' => 'uuid', // integer (default) | biginteger | uuid | binaryuuid
],
```

For `integer` and `biginteger` types, column signedness follows
`Migrations.unsigned_primary_keys`.
