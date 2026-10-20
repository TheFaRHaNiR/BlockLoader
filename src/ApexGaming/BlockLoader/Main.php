<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader;

use ApexGaming\BlockLoader\block\ExtraBlocks;
use ApexGaming\BlockLoader\task\RegisterBlocksTask;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\plugin\PluginBase;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function array_diff;
use function array_keys;
use function count;
use function implode;
use function is_array;
use function is_string;

final class Main extends PluginBase{

	/**
	 * Everything happens here, before any world is loaded. Worlds loaded later (copied minigame maps included)
	 * use the same global tables, so they are covered too.
	 */
	protected function onLoad() : void{
		$this->saveDefaultConfig();
		$config = $this->getConfig();
		$logger = $this->getLogger();

		if((bool) $config->get("upgrade-fix", true)){
			$schemas = UpgradeFix::apply();
			if(count($schemas) > 0){
				$logger->info("Re-applying " . count($schemas) . " block upgrade schema(s) for maps saved with a newer block version");
			}
		}

		$wanted = $config->get("blocks", ExtraBlocks::IDS);
		if(is_array($wanted) && count($wanted) > 0){
			$unsupported = array_diff($wanted, ExtraBlocks::IDS);
			if(count($unsupported) > 0){
				$logger->warning("Not a block BlockLoader can add (use aliases instead): " . implode(", ", $unsupported));
			}

			$typeIds = ExtraBlocks::allocate($wanted);
			if(count($typeIds) > 0){
				ExtraBlocks::registerStates(ExtraBlocks::registerRuntime($typeIds));

				$pool = $this->getServer()->getAsyncPool();
				$pool->addWorkerStartHook(function(int $worker) use ($pool, $typeIds) : void{
					$pool->submitTaskToWorker(new RegisterBlocksTask($typeIds), $worker);
				});
				$logger->info("Loaded " . count($typeIds) . " block(s): " . implode(", ", array_keys($typeIds)));
			}
		}

		$aliases = $config->get("aliases", []);
		if(is_array($aliases) && count($aliases) > 0){
			$this->registerAliases($aliases);
		}
	}

	protected function onEnable() : void{
		if((bool) $this->getConfig()->get("tile-fix", true)){
			TileFix::register($this);
		}
	}

	/**
	 * @param mixed[] $aliases
	 */
	private function registerAliases(array $aliases) : void{
		$deserializer = GlobalBlockStateHandlers::getDeserializer();
		$logger = $this->getLogger();
		$valid = [];
		foreach($aliases as $from => $to){
			if(!is_string($from) || !is_string($to)){
				$logger->warning("Skipping alias with a non-text ID");
				continue;
			}
			if($deserializer->getDeserializerForId($from) !== null){
				$logger->warning("Skipping alias $from: the server already knows this block");
				continue;
			}
			try{
				$deserializer->deserializeBlock(BlockStateData::current($to, []));
			}catch(BlockStateDeserializeException){
				$logger->warning("Skipping alias $from: $to is not a known block without states");
				continue;
			}
			$valid[$from] = $to;
		}
		if(count($valid) > 0){
			UpgradeFix::addAliases($valid);
			$logger->info("Loaded " . count($valid) . " block alias(es)");
		}
	}
}
