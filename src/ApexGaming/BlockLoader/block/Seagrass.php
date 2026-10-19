<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Flowable;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;

class Seagrass extends Flowable{

	protected SeagrassType $seagrassType = SeagrassType::DEFAULT;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->enum($this->seagrassType);
	}

	public function getSeagrassType() : SeagrassType{ return $this->seagrassType; }

	/** @return $this */
	public function setSeagrassType(SeagrassType $type) : self{
		$this->seagrassType = $type;
		return $this;
	}

	public function canBeFlowedInto() : bool{
		return false;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}
}
