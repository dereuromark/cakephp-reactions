<?php

namespace Reactions\Model\Entity;

use Cake\ORM\Entity;

/**
 * `reaction` stores the configured reaction key (emoji literal or named key).
 *
 * Foreign keys (`user_id`, `model`, `foreign_key`) and `created` are explicitly NOT
 * mass-assignable. They must always be set server-side via `ReactionsTable`, otherwise
 * any app endpoint that accepts Reaction-shaped payloads could forge reactions on behalf
 * of arbitrary users against arbitrary records.
 *
 * @property int $id
 * @property string $model
 * @property int|string $foreign_key
 * @property int $user_id
 * @property string $reaction
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\ORM\Entity $user
 */
class Reaction extends Entity {

	/**
	 * @var array<string, bool>
	 */
	protected array $_accessible = [
		'reaction' => true,
		'id' => false,
		'user_id' => false,
		'model' => false,
		'foreign_key' => false,
		'created' => false,
	];

}
