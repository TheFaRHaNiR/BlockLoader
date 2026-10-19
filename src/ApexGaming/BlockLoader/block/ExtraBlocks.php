<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Opaque;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\convert\Model;
use pocketmine\data\bedrock\block\convert\property\BoolProperty;
use pocketmine\data\bedrock\block\convert\property\CommonProperties;
use pocketmine\data\bedrock\block\convert\property\EnumFromRawStateMap;
use pocketmine\data\bedrock\block\convert\property\IntProperty;
use pocketmine\data\bedrock\block\convert\property\ValueFromStringProperty;
use pocketmine\item\ToolTier;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function in_array;

/**
 * Vanilla blocks PocketMine-MP does not implement. The client already knows all of them, so once the server can
 * read and write their states they show up exactly as in vanilla.
 */
final class ExtraBlocks{

	public const IDS = [
		"minecraft:moss_block",
		"minecraft:moss_carpet",
		"minecraft:conduit",
		"minecraft:kelp",
		"minecraft:seagrass",
		"minecraft:piston",
		"minecraft:sticky_piston",
		"minecraft:piston_arm_collision",
		"minecraft:sticky_piston_arm_collision",
		"minecraft:dropper",
		"minecraft:dispenser",
	];

	private function __construct(){
		//NOOP
	}

	/**
	 * Picks a block type ID for every requested block the server doesn't already know.
	 * Must run on the main thread only; workers get the same IDs passed to them.
	 *
	 * @param string[] $ids
	 * @return int[] block ID => type ID
	 * @phpstan-return array<string, int>
	 */
	public static function allocate(array $ids) : array{
		$deserializer = GlobalBlockStateHandlers::getDeserializer();
		$result = [];
		foreach($ids as $id){
			if(!in_array($id, self::IDS, true) || $deserializer->getDeserializerForId($id) !== null){
				continue;
			}
			$result[$id] = BlockTypeIds::newId();
		}
		return $result;
	}

	/**
	 * Adds the blocks to the state registry. Needed on the main thread and on every async worker (lighting and
	 * generation run there).
	 *
	 * @param int[] $typeIds block ID => type ID
	 * @phpstan-param array<string, int> $typeIds
	 * @return Block[]
	 * @phpstan-return array<string, Block>
	 */
	public static function registerRuntime(array $typeIds) : array{
		$registry = RuntimeBlockStateRegistry::getInstance();
		$blocks = [];
		foreach($typeIds as $id => $typeId){
			$block = self::create($id, $typeId);
			$registry->register($block);
			$blocks[$id] = $block;
		}
		return $blocks;
	}

	/**
	 * Teaches the serializer and deserializer the blocks' saved states. Main thread only.
	 *
	 * @param Block[] $blocks
	 * @phpstan-param array<string, Block> $blocks
	 */
	public static function registerStates(array $blocks) : void{
		$registrar = GlobalBlockStateHandlers::getRegistrar();
		$facing = CommonProperties::getInstance()->anyFacingClassic;

		foreach($blocks as $id => $block){
			$properties = match(true){
				$block instanceof Dropper => [
					$facing,
					new BoolProperty("triggered_bit", fn(Dropper $b) => $b->isTriggered(), fn(Dropper $b, bool $v) => $b->setTriggered($v)),
				],
				$block instanceof Piston, $block instanceof PistonArm => [$facing],
				$block instanceof Kelp => [
					new IntProperty("kelp_age", 0, Kelp::MAX_AGE, fn(Kelp $b) => $b->getAge(), fn(Kelp $b, int $v) => $b->setAge($v)),
				],
				$block instanceof Seagrass => [
					new ValueFromStringProperty(
						"sea_grass_type",
						EnumFromRawStateMap::string(SeagrassType::class, fn(SeagrassType $case) => $case->getStateValue()),
						fn(Seagrass $b) => $b->getSeagrassType(),
						fn(Seagrass $b, SeagrassType $v) => $b->setSeagrassType($v)
					),
				],
				default => null,
			};

			if($properties === null){
				$registrar->mapSimple($block, $id);
			}else{
				$registrar->mapModel(Model::create($block, $id)->properties($properties));
			}
		}
	}

	private static function create(string $id, int $typeId) : Block{
		$identifier = new BlockIdentifier($typeId);
		return match($id){
			"minecraft:moss_block" => new Opaque($identifier, "Moss Block", new BlockTypeInfo(BlockBreakInfo::hoe(0.1))),
			"minecraft:moss_carpet" => new MossCarpet($identifier, "Moss Carpet", new BlockTypeInfo(BlockBreakInfo::hoe(0.1))),
			"minecraft:conduit" => new Conduit($identifier, "Conduit", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.0))),
			"minecraft:kelp" => new Kelp($identifier, "Kelp", new BlockTypeInfo(BlockBreakInfo::instant())),
			"minecraft:seagrass" => new Seagrass($identifier, "Seagrass", new BlockTypeInfo(BlockBreakInfo::instant())),
			"minecraft:piston" => new Piston($identifier, "Piston", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:sticky_piston" => new Piston($identifier, "Sticky Piston", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:piston_arm_collision" => new PistonArm($identifier, "Piston Head", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:sticky_piston_arm_collision" => new PistonArm($identifier, "Sticky Piston Head", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:dropper" => new Dropper($identifier, "Dropper", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.5, ToolTier::WOOD))),
			"minecraft:dispenser" => new Dropper($identifier, "Dispenser", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.5, ToolTier::WOOD))),
			default => throw new \InvalidArgumentException("Unknown block ID $id"),
		};
	}
}
