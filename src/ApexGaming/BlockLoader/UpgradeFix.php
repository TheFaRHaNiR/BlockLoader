<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader;

use pocketmine\data\bedrock\block\upgrade\BlockStateUpgradeSchema;
use pocketmine\data\bedrock\block\upgrade\BlockStateUpgradeSchemaBlockRemap;
use pocketmine\data\bedrock\block\upgrade\BlockStateUpgradeSchemaUtils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Symfony\Component\Filesystem\Path;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_key_first;
use function array_map;
use function count;
use function ksort;
use function max;
use const PHP_INT_MAX;
use const pocketmine\BEDROCK_BLOCK_UPGRADE_SCHEMA_PATH;

/**
 * Mojang stopped bumping the block state version after 1.21.60.33, so every newer upgrade schema carries that same
 * version. The upgrader skips a schema when a block's saved version is higher than the schema's, which means a map
 * saved with a newer number (1.21.100 for example) never gets those upgrades: stairs miss "minecraft:corner", fences
 * miss "minecraft:connection_*", minecraft:chain is never renamed...
 *
 * This adds those schemas again under the highest possible version, so they apply to blocks of any version.
 * Only schemas that can safely run twice are re-added (renamed IDs, added and removed properties). Blocks that
 * already have the change are left as they are.
 */
final class UpgradeFix{
	/** Highest version a block can be saved with (TAG_Int max). */
	private const REPLAY_MAJOR = 127;
	private const REPLAY_MINOR = 255;
	private const REPLAY_PATCH = 255;
	private const REPLAY_REVISION = 255;

	/** Sorts after every real schema (those use 4-digit IDs). */
	private const ALIAS_SCHEMA_ID = 99999;

	private function __construct(){
		//NOOP
	}

	/**
	 * @return int[] IDs of the schemas re-added
	 */
	public static function apply() : array{
		$schemas = BlockStateUpgradeSchemaUtils::loadSchemas(
			Path::join(BEDROCK_BLOCK_UPGRADE_SCHEMA_PATH, "nbt_upgrade_schema"),
			PHP_INT_MAX
		);
		if(count($schemas) === 0){
			return [];
		}

		$latest = max(array_map(fn(BlockStateUpgradeSchema $s) => $s->getVersionId(), $schemas));
		$sameVersion = array_filter($schemas, fn(BlockStateUpgradeSchema $s) => $s->getVersionId() === $latest);
		ksort($sameVersion);
		//the first schema really belongs to this version, the ones after it reuse the number
		unset($sameVersion[array_key_first($sameVersion)]);

		$upgrader = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader();
		$added = [];
		foreach($sameVersion as $schemaId => $schema){
			if(!self::isReplaySafe($schema)){
				continue;
			}
			$replay = new BlockStateUpgradeSchema(
				self::REPLAY_MAJOR,
				self::REPLAY_MINOR,
				self::REPLAY_PATCH,
				self::REPLAY_REVISION,
				$schemaId
			);
			$replay->renamedIds = $schema->renamedIds;
			$replay->addedProperties = $schema->addedProperties;
			$replay->removedProperties = $schema->removedProperties;
			$upgrader->addSchema($replay);
			$added[] = $schemaId;
		}

		return $added;
	}

	/**
	 * Loads each "from" block ID as the stateless "to" block, dropping whatever states it had.
	 * Done as an upgrade step, so it costs the same as any other upgrade lookup.
	 *
	 * @param string[] $aliases from => to
	 * @phpstan-param array<string, string> $aliases
	 */
	public static function addAliases(array $aliases) : void{
		$schema = new BlockStateUpgradeSchema(
			self::REPLAY_MAJOR,
			self::REPLAY_MINOR,
			self::REPLAY_PATCH,
			self::REPLAY_REVISION,
			self::ALIAS_SCHEMA_ID
		);
		foreach($aliases as $from => $to){
			$schema->remappedStates[$from] = [new BlockStateUpgradeSchemaBlockRemap([], $to, [], [])];
		}
		GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader()->addSchema($schema);
	}

	/**
	 * Running the schema on a block that already went through it must change nothing.
	 */
	private static function isReplaySafe(BlockStateUpgradeSchema $schema) : bool{
		if(
			count($schema->renamedProperties) !== 0 ||
			count($schema->remappedPropertyValues) !== 0 ||
			count($schema->flattenedProperties) !== 0 ||
			count($schema->remappedStates) !== 0
		){
			return false;
		}

		//a rename whose result is renamed again would apply twice
		return count(array_intersect_key(array_flip($schema->renamedIds), $schema->renamedIds)) === 0;
	}
}
