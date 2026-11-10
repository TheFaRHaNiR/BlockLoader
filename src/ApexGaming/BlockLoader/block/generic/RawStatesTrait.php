<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block\generic;

use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\data\runtime\RuntimeDataDescriber;
use function array_fill;
use function count;

/**
 * Keeps a block's saved states as they are, one index per state into its list of allowed values (taken from
 * the client's block palette). Used for decorative blocks that only need to look right.
 */
trait RawStatesTrait{
	/**
	 * @var RawState[]
	 * @phpstan-var list<RawState>
	 */
	private array $rawStates = [];
	/**
	 * @var int[]
	 * @phpstan-var list<int>
	 */
	private array $rawIndexes = [];
	private int $rawLight = 0;

	/**
	 * The states must be known before the parent constructor runs: it works out how many bits they need.
	 *
	 * @param RawState[] $states
	 * @phpstan-param list<RawState> $states
	 */
	public function __construct(BlockIdentifier $idInfo, string $name, BlockTypeInfo $typeInfo, array $states = [], int $light = 0){
		$this->rawStates = $states;
		$this->rawIndexes = array_fill(0, count($states), 0);
		$this->rawLight = $light;
		parent::__construct($idInfo, $name, $typeInfo);
	}

	/**
	 * @return RawState[]
	 * @phpstan-return list<RawState>
	 */
	public function getRawStates() : array{ return $this->rawStates; }

	public function getRawIndex(int $state) : int{ return $this->rawIndexes[$state]; }

	/** @return $this */
	public function setRawIndex(int $state, int $index) : self{
		if($index < 0 || $index >= count($this->rawStates[$state]->values)){
			throw new \InvalidArgumentException("Index $index out of range for " . $this->rawStates[$state]->name);
		}
		$this->rawIndexes[$state] = $index;
		return $this;
	}

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		foreach($this->rawStates as $i => $state){
			$w->boundedIntAuto(0, count($state->values) - 1, $this->rawIndexes[$i]);
		}
	}

	public function getLightLevel() : int{
		return $this->rawLight;
	}
}
