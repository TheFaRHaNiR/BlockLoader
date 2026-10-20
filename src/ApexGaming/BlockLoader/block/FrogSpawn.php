<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Flowable;
use pocketmine\item\Item;

/**
 * Decorative frog spawn: sits on water, does not hatch.
 */
class FrogSpawn extends Flowable{

	public function canBeFlowedInto() : bool{
		return false;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}
}
