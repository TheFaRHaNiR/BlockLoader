<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\task;

use ApexGaming\BlockLoader\block\ExtraBlocks;
use pocketmine\scheduler\AsyncTask;
use function igbinary_serialize;
use function igbinary_unserialize;

/**
 * Registers the blocks on an async worker so lighting and generation there know their light values.
 * The type IDs come from the main thread so state IDs are identical on both sides.
 */
final class RegisterBlocksTask extends AsyncTask{

	private string $typeIds;

	/**
	 * @param int[] $typeIds block ID => type ID
	 * @phpstan-param array<string, int> $typeIds
	 */
	public function __construct(array $typeIds){
		$this->typeIds = igbinary_serialize($typeIds) ?? throw new \RuntimeException("Failed to serialize type IDs");
	}

	public function onRun() : void{
		/** @phpstan-var array<string, int> $typeIds */
		$typeIds = igbinary_unserialize($this->typeIds);
		ExtraBlocks::registerRuntime($typeIds);
	}
}
