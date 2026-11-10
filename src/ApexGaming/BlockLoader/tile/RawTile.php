<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\tile;

use pocketmine\block\tile\Spawnable;
use pocketmine\block\tile\Tile;
use pocketmine\block\tile\TileFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\world\World;

/**
 * A block entity PocketMine doesn't know, kept exactly as saved: written back unchanged and sent to clients,
 * so e.g. decorated pots keep their patterns. Without this PocketMine deletes it ("Deleted unknown tile entity
 * type ...").
 */
abstract class RawTile extends Spawnable{
	private const OWN_TAGS = [Tile::TAG_ID, Tile::TAG_X, Tile::TAG_Y, Tile::TAG_Z];

	private CompoundTag $data;

	public function __construct(World $world, Vector3 $pos){
		$this->data = CompoundTag::create();
		parent::__construct($world, $pos);
	}

	/**
	 * Registers the block entities BlockLoader keeps, under their vanilla save IDs.
	 */
	public static function registerAll() : void{
		$factory = TileFactory::getInstance();
		foreach([PistonArm::class, DecoratedPot::class, Beehive::class] as $class){
			if(!$factory->isRegistered($class)){
				$factory->register($class);
			}
		}
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$data = clone $nbt;
		foreach(self::OWN_TAGS as $tag){
			$data->removeTag($tag);
		}
		$this->data = $data;
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$this->copyTo($nbt);
	}

	protected function addAdditionalSpawnData(CompoundTag $nbt, TypeConverter $typeConverter) : void{
		$this->copyTo($nbt);
	}

	private function copyTo(CompoundTag $nbt) : void{
		foreach($this->data->getValue() as $name => $tag){
			$nbt->setTag($name, clone $tag);
		}
	}
}
