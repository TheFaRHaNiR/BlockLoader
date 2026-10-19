<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Transparent;
use pocketmine\block\utils\SupportType;
use pocketmine\math\AxisAlignedBB;

class Conduit extends Transparent{

	public function getLightLevel() : int{
		return 15;
	}

	protected function recalculateCollisionBoxes() : array{
		return [AxisAlignedBB::one()->contract(5 / 16, 5 / 16, 5 / 16)];
	}

	public function getSupportType(int $facing) : SupportType{
		return SupportType::NONE;
	}
}
