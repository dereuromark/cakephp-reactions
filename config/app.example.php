<?php

use Cake\Http\ServerRequest;

return [
	'Reactions' => [
		'model' => null, // Auto-detect
		'modelClass' => null, // Auto-detect
		'reactionClass' => 'Reactions.Reactions',
		'userModel' => 'Users',
		'userModelClass' => 'Users',
		'userModelConfig' => null,
		'allowed' => ['👍', '👎', '❤️', 'rocket'], // null = allow any reaction key
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
