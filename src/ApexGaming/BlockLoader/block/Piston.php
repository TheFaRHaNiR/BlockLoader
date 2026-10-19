<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Opaque;
use pocketmine\block\utils\AnyFacing;
use pocketmine\block\utils\AnyFacingTrait;

/**
 * Decorative piston: keeps its facing, does not push.
 */
class Piston extends Opaque implements AnyFacing{
	use AnyFacingTrait;
}
