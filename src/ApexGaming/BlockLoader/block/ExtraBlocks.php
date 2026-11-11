<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

use ApexGaming\BlockLoader\block\generic\GenericFlowable;
use ApexGaming\BlockLoader\block\generic\GenericOpaque;
use ApexGaming\BlockLoader\block\generic\GenericTransparent;
use ApexGaming\BlockLoader\block\generic\RawState;
use ApexGaming\BlockLoader\tile\Beehive as BeehiveTile;
use ApexGaming\BlockLoader\tile\DecoratedPot as DecoratedPotTile;
use ApexGaming\BlockLoader\tile\Lodestone as LodestoneTile;
use ApexGaming\BlockLoader\tile\PistonArm as PistonArmTile;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockToolType;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\BlockTypeTags;
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
use function count;
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
		"minecraft:composter",
		"minecraft:frog_spawn",
		"minecraft:decorated_pot",
		"minecraft:cherry_sapling",
		"minecraft:mangrove_propagule",
		"minecraft:end_portal",
		"minecraft:beehive",
		"minecraft:bee_nest",
		"minecraft:honey_block",
		"minecraft:scaffolding",
		"minecraft:turtle_egg",
		"minecraft:grindstone",
		"minecraft:target",
		"minecraft:pointed_dripstone",
		"minecraft:lodestone",
		"minecraft:dripstone_block",
	];

	private const OPAQUE = "opaque";
	private const TRANSPARENT = "transparent";
	private const NO_COLLISION = "no_collision";

	/**
	 * Decorative blocks whose states are kept exactly as saved. States and their values are the client's own
	 * (canonical block palette of Bedrock 1.26.50), in the order the client lists them.
	 *
	 * id => [shape, name, hardness, tool, light, tile class, type tags, [state => [type, values]]]
	 */
	private static function genericDefinitions() : array{
		$direction = ["direction" => [RawState::INT, [0, 1, 2, 3]]];
		$hive = $direction + ["honey_level" => [RawState::INT, [0, 1, 2, 3, 4, 5]]];
		$pottable = [BlockTypeTags::POTTABLE_PLANTS];
		return [
			"minecraft:decorated_pot" => [self::TRANSPARENT, "Decorated Pot", 0.0, BlockToolType::NONE, 0, DecoratedPotTile::class, [], $direction],
			"minecraft:cherry_sapling" => [self::NO_COLLISION, "Cherry Sapling", 0.0, BlockToolType::NONE, 0, null, $pottable, ["age_bit" => [RawState::BOOL, [0, 1]]]],
			"minecraft:mangrove_propagule" => [self::NO_COLLISION, "Mangrove Propagule", 0.0, BlockToolType::NONE, 0, null, $pottable, [
				"hanging" => [RawState::BOOL, [0, 1]],
				"propagule_stage" => [RawState::INT, [0, 1, 2, 3, 4]],
			]],
			"minecraft:end_portal" => [self::NO_COLLISION, "End Portal", -1.0, BlockToolType::NONE, 15, null, [], []],
			"minecraft:beehive" => [self::OPAQUE, "Beehive", 0.6, BlockToolType::AXE, 0, BeehiveTile::class, [], $hive],
			"minecraft:bee_nest" => [self::OPAQUE, "Bee Nest", 0.3, BlockToolType::AXE, 0, BeehiveTile::class, [], $hive],
			"minecraft:honey_block" => [self::TRANSPARENT, "Honey Block", 0.0, BlockToolType::NONE, 0, null, [], []],
			"minecraft:scaffolding" => [self::TRANSPARENT, "Scaffolding", 0.0, BlockToolType::NONE, 0, null, [], [
				"stability" => [RawState::INT, [0, 1, 2, 3, 4, 5, 6, 7]],
				"stability_check" => [RawState::BOOL, [0, 1]],
			]],
			"minecraft:turtle_egg" => [self::TRANSPARENT, "Turtle Egg", 0.5, BlockToolType::NONE, 0, null, [], [
				"cracked_state" => [RawState::STRING, ["no_cracks", "cracked", "max_cracked"]],
				"turtle_egg_count" => [RawState::STRING, ["one_egg", "two_egg", "three_egg", "four_egg"]],
			]],
			"minecraft:grindstone" => [self::TRANSPARENT, "Grindstone", 2.0, BlockToolType::PICKAXE, 0, null, [], [
				"attachment" => [RawState::STRING, ["standing", "hanging", "side", "multiple"]],
			] + $direction],
			"minecraft:target" => [self::OPAQUE, "Target", 0.5, BlockToolType::HOE, 0, null, [], []],
			"minecraft:pointed_dripstone" => [self::TRANSPARENT, "Pointed Dripstone", 1.5, BlockToolType::PICKAXE, 0, null, [], [
				"dripstone_thickness" => [RawState::STRING, ["tip", "frustum", "middle", "base", "merge"]],
				"hanging" => [RawState::BOOL, [0, 1]],
			]],
			"minecraft:dripstone_block" => [self::OPAQUE, "Dripstone Block", 1.5, BlockToolType::PICKAXE, 0, null, [], []],
			"minecraft:lodestone" => [self::OPAQUE, "Lodestone", 3.5, BlockToolType::PICKAXE, 0, LodestoneTile::class, [], []],
		];
	}

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
	 * Teaches the serializer and deserializer the blocks' saved states. Needed on the main thread and on every async
	 * worker, since chunks are encoded for players there.
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
				$block instanceof GenericOpaque, $block instanceof GenericTransparent, $block instanceof GenericFlowable =>
					count($block->getRawStates()) > 0 ? $block->getRawStates() : null,
				$block instanceof Composter => [
					new IntProperty("composter_fill_level", 0, Composter::MAX_FILL_LEVEL, fn(Composter $b) => $b->getFillLevel(), fn(Composter $b, int $v) => $b->setFillLevel($v)),
				],
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
		$generic = self::genericDefinitions()[$id] ?? null;
		if($generic !== null){
			return self::createGeneric($typeId, ...$generic);
		}
		$identifier = new BlockIdentifier($typeId);
		$piston = new BlockIdentifier($typeId, PistonArmTile::class);
		return match($id){
			"minecraft:moss_block" => new Opaque($identifier, "Moss Block", new BlockTypeInfo(BlockBreakInfo::hoe(0.1))),
			"minecraft:moss_carpet" => new MossCarpet($identifier, "Moss Carpet", new BlockTypeInfo(BlockBreakInfo::hoe(0.1))),
			"minecraft:conduit" => new Conduit($identifier, "Conduit", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.0))),
			"minecraft:kelp" => new Kelp($identifier, "Kelp", new BlockTypeInfo(BlockBreakInfo::instant())),
			"minecraft:seagrass" => new Seagrass($identifier, "Seagrass", new BlockTypeInfo(BlockBreakInfo::instant())),
			"minecraft:piston" => new Piston($piston, "Piston", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:sticky_piston" => new Piston($piston, "Sticky Piston", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:piston_arm_collision" => new PistonArm($identifier, "Piston Head", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:sticky_piston_arm_collision" => new PistonArm($identifier, "Sticky Piston Head", new BlockTypeInfo(BlockBreakInfo::pickaxe(1.5))),
			"minecraft:dropper" => new Dropper($identifier, "Dropper", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.5, ToolTier::WOOD))),
			"minecraft:dispenser" => new Dropper($identifier, "Dispenser", new BlockTypeInfo(BlockBreakInfo::pickaxe(3.5, ToolTier::WOOD))),
			"minecraft:composter" => new Composter($identifier, "Composter", new BlockTypeInfo(BlockBreakInfo::axe(0.6))),
			"minecraft:frog_spawn" => new FrogSpawn($identifier, "Frog Spawn", new BlockTypeInfo(BlockBreakInfo::instant())),
			default => throw new \InvalidArgumentException("Unknown block ID $id"),
		};
	}

	/**
	 * @param string[] $typeTags
	 * @param mixed[]  $states state name => [type, values]
	 * @phpstan-param class-string<\pocketmine\block\tile\Tile>|null $tileClass
	 * @phpstan-param array<string, array{string, list<int>|list<string>}> $states
	 */
	private static function createGeneric(int $typeId, string $shape, string $name, float $hardness, int $tool, int $light, ?string $tileClass, array $typeTags, array $states) : Block{
		$identifier = new BlockIdentifier($typeId, $tileClass);
		$breakInfo = $hardness < 0 ? BlockBreakInfo::indestructible() : new BlockBreakInfo($hardness, $tool);
		$info = new BlockTypeInfo($breakInfo, $typeTags);
		$rawStates = [];
		foreach($states as $stateName => [$type, $values]){
			$rawStates[] = new RawState(count($rawStates), $stateName, $type, $values);
		}
		return match($shape){
			self::OPAQUE => new GenericOpaque($identifier, $name, $info, $rawStates, $light),
			self::TRANSPARENT => new GenericTransparent($identifier, $name, $info, $rawStates, $light),
			self::NO_COLLISION => new GenericFlowable($identifier, $name, $info, $rawStates, $light),
		};
	}
}
