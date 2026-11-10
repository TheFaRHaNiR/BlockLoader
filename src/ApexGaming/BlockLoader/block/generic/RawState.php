<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block\generic;

use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\data\bedrock\block\convert\property\Property;
use function array_search;

/**
 * One saved block state and its allowed values, read and written exactly as the client's block palette has them.
 *
 * @phpstan-implements Property<GenericOpaque|GenericTransparent|GenericFlowable>
 */
final class RawState implements Property{
	public const INT = "int";
	public const BOOL = "bool";
	public const STRING = "string";

	/**
	 * @param int[]|string[] $values
	 * @phpstan-param list<int>|list<string> $values
	 */
	public function __construct(
		public readonly int $index,
		public readonly string $name,
		public readonly string $type,
		public readonly array $values
	){}

	public function getName() : string{ return $this->name; }

	public function deserialize(object $block, BlockStateReader $in) : void{
		$raw = match($this->type){
			self::INT => $in->readInt($this->name),
			self::BOOL => (int) $in->readBool($this->name),
			self::STRING => $in->readString($this->name),
		};
		$found = array_search($raw, $this->values, true);
		if($found === false){
			throw new BlockStateDeserializeException("Unexpected value $raw for state $this->name");
		}
		$block->setRawIndex($this->index, $found);
	}

	public function serialize(object $block, BlockStateWriter $out) : void{
		$value = $this->values[$block->getRawIndex($this->index)];
		match($this->type){
			self::INT => $out->writeInt($this->name, (int) $value),
			self::BOOL => $out->writeBool($this->name, $value === 1),
			self::STRING => $out->writeString($this->name, (string) $value),
		};
	}
}
