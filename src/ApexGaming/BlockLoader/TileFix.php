<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader;

use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Facing;
use pocketmine\plugin\Plugin;

/**
 * Some maps have chests (and furnaces, barrels, hoppers...) whose block entity was never saved, usually from
 * WorldEdit pastes or world converters. Vanilla quietly recreates it; PocketMine doesn't, so the block does
 * nothing when clicked.
 *
 * When a player clicks such a block, it is written again in place, the same as breaking and placing it, which
 * creates the missing tile before the click is handled, so it opens on that same click. Double chests are paired
 * like vanilla placement. Nothing runs on chunk load.
 */
final class TileFix{

	public static function register(Plugin $plugin) : void{
		$plugin->getServer()->getPluginManager()->registerEvent(
			PlayerInteractEvent::class,
			static function(PlayerInteractEvent $event) : void{
				if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
					self::fix($event->getBlock());
				}
			},
			EventPriority::LOWEST, //before other plugins (e.g. minigame loot) look at the tile
			$plugin
		);
	}

	/**
	 * @return bool whether a missing tile was created
	 */
	public static function fix(Block $block) : bool{
		if(!self::rewriteIfTileMissing($block)){
			return false;
		}

		if($block instanceof Chest){
			$world = $block->getPosition()->getWorld();
			//the other half of a double chest is usually missing its tile too
			foreach([false, true] as $clockwise){
				$side = $block->getSide(Facing::rotateY($block->getFacing(), $clockwise));
				if($side instanceof Chest && $side->hasSameTypeId($block) && $side->getFacing() === $block->getFacing()){
					self::rewriteIfTileMissing($side);
				}
			}
			$world->getBlock($block->getPosition())->onPostPlace(); //vanilla pairing
		}
		return true;
	}

	private static function rewriteIfTileMissing(Block $block) : bool{
		if($block->getIdInfo()->getTileClass() === null){
			return false;
		}
		$pos = $block->getPosition();
		$world = $pos->getWorld();
		if(!$world->isChunkLoaded($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4) || $world->getTile($pos) !== null){
			return false;
		}
		$world->setBlock($pos, $block, false);
		return $world->getTile($pos) !== null;
	}
}
