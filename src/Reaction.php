<?php
declare(strict_types=1);

namespace Reactions;

/**
 * Default reaction set — the eight GitHub-style emoji reactions, ready to use
 * as a typed alternative to magic strings:
 *
 * ```php
 * $this->Articles
 *     ->getBehavior('Reactable')
 *     ->react($articleId, by: $userId, with: Reaction::ThumbsUp);
 * ```
 *
 * Apps with their own set (named keys, different emoji, internal subsets)
 * should declare their own `string`-backed enum. `ReactableBehavior` and
 * `ReactionsController` accept any `string|\BackedEnum` for the reaction
 * value, so the plugin remains open to arbitrary keys.
 */
enum Reaction: string {

	case ThumbsUp = '👍';
	case ThumbsDown = '👎';
	case Laugh = '😄';
	case Confused = '😕';
	case Heart = '❤️';
	case Party = '🎉';
	case Rocket = '🚀';
	case Eyes = '👀';

}
