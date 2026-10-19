<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Transparent;
use pocketmine\block\utils\AnyFacing;
use pocketmine\block\utils\AnyFacingTrait;
use pocketmine\item\Item;

class PistonArm extends Transparent implements AnyFacing{
	use AnyFacingTrait;

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}
}
