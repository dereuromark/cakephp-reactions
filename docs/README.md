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

## Counter cache

To denormalize the total number of reactions on each host record (avoids a JOIN /
sub-select when listing many records), add a counter column to the host table and
enable `counterCache`:

```php
// Migration: add the counter column to your host table
$this->table('articles')
	->addColumn('reactions_count', 'integer', ['default' => 0, 'null' => false])
	->update();
```

```php
$this->addBehavior('Reactions.Reactable', [
	'counterCache' => true,
	// 'fieldCounter' => 'reactions_count', // default
]);
```

The column is refreshed after every `addReaction()`, `removeReaction()`, and
`toggleReaction()` call. If the configured column does not exist on the host
table the behavior silently skips the write so apps can enable the flag before
running the schema change.

> [!NOTE]
> The counter is recomputed from the reactions table on every change (not
> incremented), so it is self-healing even after manual edits. It does NOT cover
> direct `ReactionsTable::add()` / `remove()` calls — go through the behavior to
> keep the counter accurate.

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
