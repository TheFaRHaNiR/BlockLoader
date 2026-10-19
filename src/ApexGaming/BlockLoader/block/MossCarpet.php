<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Flowable;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;

class MossCarpet extends Flowable{

	public function isSolid() : bool{
		return true;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->trim(Facing::UP, 15 / 16)];
	}
}
