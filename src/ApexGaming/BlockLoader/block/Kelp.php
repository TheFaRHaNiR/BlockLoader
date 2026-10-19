<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Flowable;
use pocketmine\data\runtime\RuntimeDataDescriber;

class Kelp extends Flowable{
	public const MAX_AGE = 25;

	protected int $age = 0;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedIntAuto(0, self::MAX_AGE, $this->age);
	}

	public function getAge() : int{ return $this->age; }

	/** @return $this */
	public function setAge(int $age) : self{
		if($age < 0 || $age > self::MAX_AGE){
			throw new \InvalidArgumentException("Age must be in range 0 ... " . self::MAX_AGE);
		}
		$this->age = $age;
		return $this;
	}

	public function canBeFlowedInto() : bool{
		return false;
	}
}
