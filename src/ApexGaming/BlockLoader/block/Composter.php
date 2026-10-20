<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Transparent;
use pocketmine\data\runtime\RuntimeDataDescriber;

/**
 * Decorative composter: keeps its fill level, does not compost.
 */
class Composter extends Transparent{
	public const MAX_FILL_LEVEL = 8;

	protected int $fillLevel = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_FILL_LEVEL, $this->fillLevel);
	}

	public function getFillLevel() : int{ return $this->fillLevel; }

	/** @return $this */
	public function setFillLevel(int $level) : self{
		if($level < 0 || $level > self::MAX_FILL_LEVEL){
			throw new \InvalidArgumentException("Fill level must be in range 0 ... " . self::MAX_FILL_LEVEL);
		}
		$this->fillLevel = $level;
		return $this;
	}
}
