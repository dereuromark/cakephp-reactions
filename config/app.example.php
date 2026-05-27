<?php

use Cake\Http\ServerRequest;

return [
	// Global schema knobs read by the Reactions migration (app-level keys, NOT
	// namespaced under "Reactions"). They shape the reactions_reactions table so its
	// foreign_key matches your host records' primary keys. Shown with their defaults.
	'Polymorphic' => [
		// reactions_reactions.foreign_key column type.
		'type' => 'integer', // integer (default) | biginteger | uuid | binaryuuid
	],
	'Migrations' => [
		// Aligns foreign-key signedness with your primary keys (integer/biginteger only).
		'unsigned_primary_keys' => false,
	],

	'Reactions' => [
		'model' => null, // Auto-detect
		'modelClass' => null, // Auto-detect
		'reactionClass' => 'Reactions.Reactions',
		'userModel' => 'Users',
		'userModelClass' => 'Users',
		'userModelConfig' => null,
		// Allow list: strings, `Reactions\Reaction` (or any string-backed enum) cases, or a mix.
		// Set to null to accept any reaction key.
		'allowed' => ['👍', '👎', '❤️', 'rocket'],
		'counterCache' => false,
		'fieldCounter' => 'reactions_count',
		'models' => [
			'Posts' => 'MyPlugin.Posts',
		],
		'userIdField' => 'id',
		'sessionKey' => 'Auth.User',
		'adminAccess' => function (ServerRequest $request): bool {
			$identity = $request->getAttribute('identity');

			return $identity !== null && in_array('admin', (array)$identity->roles, true);
		},
	],
];
