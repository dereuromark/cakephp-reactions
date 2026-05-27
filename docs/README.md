# Reactions Plugin Docs

The Reactions plugin adds GitHub-style reactions to any CakePHP model. Each
reaction is stored as one row containing:

- the host model name,
- the host record primary key,
- the reacting user id,
- the reaction key.

A user can react to the same record with multiple different reaction keys, for
example `👍` and `🎉`, but the same user cannot store the same reaction twice for
the same record.

## Contents

- [Install](#install)
- [Quick Start](#quick-start)
- [Choosing A Request Strategy](#choosing-a-request-strategy)
- [Behavior API](#behavior-api)
- [Helper](#helper)
- [Configuration](#configuration)
- [Counter Cache](#counter-cache)
- [User Model](#user-model)
- [Admin Backend](#admin-backend)
- [Database Configuration](#database-configuration)
- [Security Notes](#security-notes)
- [Troubleshooting](#troubleshooting)
- [Detailed Docs](#detailed-docs)

## Install

```bash
composer require dereuromark/cakephp-reactions
```

Load the plugin and run the migration:

```bash
bin/cake plugin load Reactions
bin/cake migrations migrate -p Reactions
```

For branch compatibility, see the
[CakePHP version map](https://github.com/dereuromark/cakephp-reactions/wiki#cakephp-version-map).

## Quick Start

### 1. Attach The Behavior

Attach `Reactable` to every model that can receive reactions:

```php
// in src/Model/Table/PostsTable.php
public function initialize(array $config): void {
	parent::initialize($config);

	$this->addBehavior('Reactions.Reactable', [
		'allowed' => ['👍', '👎', '❤️', '🎉', '🚀'],
	]);
}
```

### 2. Configure Public Model Aliases

When you use the plugin controller or helper, map short public aliases to real
table classes:

```php
// in config/app_local.php or another loaded config file
'Reactions' => [
	'models' => [
		'Posts' => 'Posts',
		'Articles' => 'Blog.Articles',
	],
	'allowed' => ['👍', '👎', '❤️', '🎉', '🚀'],
],
```

The alias is part of the public URL and form payload. Keep it stable, short, and
explicitly configured.

### 3. Load The Helper

```php
// in src/View/AppView.php
public function initialize(): void {
	parent::initialize();

	$this->loadHelper('Reactions.Reactions');
}
```

### 4. Render Reactions

```php
// in templates/Posts/view.php
echo $this->Reactions->widget('Posts', $post->id);
echo $this->Reactions->counts('Posts', $post->id);
```

The widget renders reaction links with `FormHelper::postLink()` and `block =>
true`. Your layout must output the `postLink` block once:

```php
// before </body> in templates/layout/default.php
<?= $this->fetch('postLink') ?>
```

### 5. Use The Behavior Directly When Needed

```php
$this->Posts->addReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);

$result = $this->Posts->toggleReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '🚀',
]);

$counts = $this->Posts->reactionCounts($postId);
$userReactions = $this->Posts->userReactions($postId, $userId);
```

## Choosing A Request Strategy

The plugin supports two UI request strategies.

### Controller Strategy

This is the default helper strategy. The helper posts to
`Reactions.ReactionsController`:

```php
echo $this->Reactions->widget('Posts', $post->id);
```

Use this when you want a conventional route such as:

```text
/reactions/reactions/toggle/Posts/123
```

Requirements:

- configure `Reactions.models`,
- make sure the current user id is available from the configured session key,
- load the helper in your view layer.

For JSON or AJAX requests to `toggle`, the controller returns:

```json
{
	"action": "added",
	"counts": {
		"👍": 3,
		"🚀": 1
	}
}
```

### Action Strategy

The action strategy posts back to the current host controller action and lets
`ReactableComponent` process the reaction payload before your normal response.

Load the component:

```php
// in src/Controller/PostsController.php
public function initialize(): void {
	parent::initialize();

	$this->loadComponent('Reactions.Reactable');
}
```

Load the helper with the action strategy:

```php
$this->loadHelper('Reactions.Reactions', [
	'strategy' => 'action',
]);
```

The helper includes `alias`, `id`, `reaction`, and `action` in the POST data.
Use this when you want reaction submissions handled inside an existing page flow
instead of the plugin controller.

Component options:

| Option | Default | Description |
| ------ | ------- | ----------- |
| `on` | `startup` | Event used for processing. Use `beforeRender` if `useEntity` is enabled. |
| `actions` | `['view', 'reactions']` | Controller actions where the component is active. Set an empty value to allow all actions. |
| `useEntity` | `false` | Read the id from the current view variable instead of POST data. Requires `on => 'beforeRender'`. |
| `sessionKey` | `Auth.User` | Session path used to read the current user. |
| `userIdField` | `id` | User id field inside the configured session path. |

## Behavior API

`ReactableBehavior` is the lowest-level public API for host models.

### Short, Typed Aliases

`react()`, `unreact()`, and `toggle()` accept any `string|\BackedEnum` so you can use the bundled `Reactions\Reaction` enum — or your own — instead of bare emoji strings:

```php
use Reactions\Reaction;

$behavior = $this->Posts->getBehavior('Reactable');

$behavior->react($postId, by: $userId, with: Reaction::ThumbsUp);
$behavior->unreact($postId, by: $userId, with: Reaction::ThumbsUp);
$behavior->toggle($postId, by: $userId, with: Reaction::Rocket);
```

These aliases live on the behavior instance only — they are intentionally not registered as table magic methods (`react`/`unreact`/`toggle` are too generic and would collide with other behaviors that expose the same names). The array-form methods below also accept BackedEnum cases in the `reaction` slot.

### Add

```php
$reactionId = $this->Posts->addReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);
```

`addReaction()` is idempotent. It returns the new reaction id when a row is
created, and `null` when the same user already stored that reaction for that
record.

### Remove

```php
$deleted = $this->Posts->removeReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);
```

`removeReaction()` returns the number of deleted rows.

### Toggle

```php
$result = $this->Posts->toggleReaction([
	'modelId' => $postId,
	'userId' => $userId,
	'reaction' => '👍',
]);
```

The result contains the action and fresh counts:

```php
[
	'action' => 'added', // or 'removed'
	'counts' => [
		'👍' => 3,
	],
]
```

### Counts And User Reactions

```php
$counts = $this->Posts->reactionCounts($postId);
$mine = $this->Posts->userReactions($postId, $userId);
```

### Finders

```php
$post = $this->Posts->find('reactions', id: $postId)->firstOrFail();
$posts = $this->Posts->find('reactedBy', userId: $userId)->all();
```

## Helper

Load the helper:

```php
$this->loadHelper('Reactions.Reactions');
```

Render the interactive widget:

```php
echo $this->Reactions->widget('Posts', $post->id);
```

Render only counts:

```php
echo $this->Reactions->counts('Posts', $post->id);
```

Get the toggle URL if you need custom markup:

```php
$url = $this->Reactions->urlToggle('Posts', $post->id);
```

Customize icons and markup:

```php
$this->loadHelper('Reactions.Reactions', [
	'icons' => [
		'thumbsup' => '👍',
		'heart' => '❤️',
		'rocket' => '🚀',
	],
	'html' => '<span class="reaction%s" aria-hidden="true">%s</span>',
]);
```

The default icon set is GitHub-like:

```php
['👍', '👎', '😄', '😕', '❤️', '🎉', '🚀', '👀']
```

## Configuration

The plugin reads defaults from `Configure::read('Reactions')`. You can configure
globally or per behavior/helper/component load.

```php
'Reactions' => [
	'reactionClass' => 'Reactions.Reactions',
	'userModel' => 'Users',
	'userModelClass' => 'Users',
	'userModelConfig' => null,
	'allowed' => ['👍', '👎', '❤️', '🎉', '🚀'],
	'counterCache' => false,
	'fieldCounter' => 'reactions_count',
	'models' => [
		'Posts' => 'Posts',
	],
	'userIdField' => 'id',
	'sessionKey' => 'Auth.User',
],
```

| Key | Used By | Description |
| --- | ------- | ----------- |
| `reactionClass` | behavior, helper | Table class used for reaction rows. |
| `userModel` | behavior, table | Association alias for users on the reactions table. |
| `userModelClass` | behavior, table | User table class name. |
| `userModelConfig` | behavior | Full `belongsTo()` config for custom user associations. |
| `allowed` | behavior, controller | `null` allows any key; an array restricts accepted reaction keys. |
| `counterCache` | behavior | Enables recomputed reaction totals on the host record. |
| `fieldCounter` | behavior | Host table column used for the counter cache. |
| `models` | controller, admin | Public alias to table class map. |
| `userIdField` | controller, component | Field used to read the user id from session data. |
| `sessionKey` | controller, component | Session path for the authenticated user. |
| `adminAccess` | admin controller | Closure that must return literal `true` to allow admin access. |

## Counter Cache

Counter cache is optional. It stores the total number of reactions on the host
record so list pages can avoid counting rows for every record.

Add a column to each host table that needs it:

```php
$this->table('posts')
	->addColumn('reactions_count', 'integer', [
		'default' => 0,
		'null' => false,
	])
	->update();
```

Enable the behavior option:

```php
$this->addBehavior('Reactions.Reactable', [
	'counterCache' => true,
	// 'fieldCounter' => 'reactions_count',
]);
```

The counter is recomputed from the reactions table after `addReaction()`,
`removeReaction()`, and `toggleReaction()`. Recomputing is slightly more work
than incrementing, but it self-heals after manual row changes.

The plugin controller also refreshes the counter when the host table has
`Reactable` loaded. Direct calls to `ReactionsTable::add()` or
`ReactionsTable::remove()` bypass the behavior and should only be used when you
handle counter refreshes yourself.

If the configured counter column does not exist, the write is silently skipped.
This lets you deploy behavior config before the schema change reaches every
environment.

## User Model

By default reactions belong to `Users` through `user_id`.

For a different user table class:

```php
$this->addBehavior('Reactions.Reactable', [
	'userModelClass' => 'Accounts',
]);
```

For a custom association:

```php
$this->addBehavior('Reactions.Reactable', [
	'userModel' => 'Authors',
	'userModelConfig' => [
		'className' => 'Accounts.Users',
		'foreignKey' => 'user_id',
	],
]);
```

The reaction table validation rules use this association when checking that
`user_id` exists.

## Admin Backend

The admin backend lists reaction rows and can filter by configured models:

```text
/admin/reactions
```

Access is deny-by-default. Configure `Reactions.adminAccess` with a Closure that
returns literal `true` for allowed requests:

```php
use Cake\Http\ServerRequest;

'Reactions' => [
	'adminAccess' => function (ServerRequest $request): bool {
		$identity = $request->getAttribute('identity');

		return $identity !== null && in_array('admin', (array)$identity->roles, true);
	},
],
```

## Database Configuration

### Polymorphic Foreign Key Type

The `reactions_reactions.foreign_key` column type is configurable via the global
`Polymorphic.type` key. This key is not nested under `Reactions`.

```php
'Polymorphic' => [
	'type' => 'uuid', // integer (default) | biginteger | uuid | binaryuuid
],
```

For `integer` and `biginteger` types, column signedness follows
`Migrations.unsigned_primary_keys`:

```php
'Migrations' => [
	'unsigned_primary_keys' => false,
],
```

Set these before running the plugin migration so the reaction foreign key matches
your host record primary keys.

### Reaction Column

The `reaction` column accepts emoji literals and named keys. On MySQL the
migration uses `utf8mb4_bin` for the reaction column so emoji are grouped and
deduplicated byte-for-byte.

## Security Notes

- Do not trust submitted `user_id` values. The plugin reads the user id from the
  configured session path or from the server-side behavior call.
- Configure `Reactions.models` for every model exposed through the controller
  strategy. Unknown aliases are rejected.
- Use `allowed` when user-submitted arbitrary reaction keys are not acceptable.
- Reaction entity foreign keys are not mass assignable; write reactions through
  the table or behavior APIs.
- The admin backend is disabled until `adminAccess` explicitly returns `true`.

## Troubleshooting

### Reaction Buttons Render But Do Nothing

Make sure your layout outputs the `postLink` block:

```php
<?= $this->fetch('postLink') ?>
```

The helper uses `block => true` so reaction forms are moved out of surrounding
HTML forms.

### Invalid Alias

The plugin controller only accepts aliases configured under `Reactions.models`:

```php
'Reactions' => [
	'models' => [
		'Posts' => 'Posts',
	],
],
```

The first argument to `widget()` must be the alias, not necessarily the table
class:

```php
echo $this->Reactions->widget('Posts', $post->id);
```

### Must Be Logged In

Controller and component flows read the current user from:

```php
'Reactions' => [
	'sessionKey' => 'Auth.User',
	'userIdField' => 'id',
],
```

Adjust these values if your authentication data is stored elsewhere.

### Counter Stays At Zero

Check that:

- `counterCache` is enabled on the host table behavior,
- the configured `fieldCounter` column exists on the host table,
- writes go through the behavior or plugin controller,
- the stored model string matches the rows you expect to count.

### Custom User Table Fails Exists-In Rules

Set `userModel`, `userModelClass`, or `userModelConfig` on the behavior so the
reaction table can validate `user_id` against the right association.

### Emoji Reactions Collapse Together On MySQL

Run the plugin migration as provided. The reaction column uses a binary collation
on MySQL to avoid case/accent-insensitive comparisons treating distinct emoji as
the same value.

## Detailed Docs

- [Reaction API](Reaction.md)
