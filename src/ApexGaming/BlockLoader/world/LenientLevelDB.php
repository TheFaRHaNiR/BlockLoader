<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\world;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Binary;
use pocketmine\world\format\io\exception\CorruptedChunkException;
use pocketmine\world\format\io\leveldb\ChunkDataKey;
use pocketmine\world\format\io\leveldb\LevelDB;
use pocketmine\world\format\io\LoadedChunkData;
use pocketmine\world\format\io\WorldProviderManager;
use pocketmine\world\format\io\WritableWorldProviderManagerEntry;
use function ord;
use function str_contains;
use function strlen;
use function substr;

/**
 * The normal LevelDB provider, plus one repair: some world tools save a chunk's 3D biomes starting with the
 * "same as the previous sub-chunk" marker, when there is no previous one. PocketMine rejects the whole chunk
 * ("Serialized biome palette 0 has no previous palette to copy from") and its terrain never loads.
 *
 * Only when a chunk fails with exactly that error, the first marker is replaced with a single plains biome, the
 * repaired data is written back to that chunk and it is loaded again. Healthy chunks load exactly as before.
 */
class LenientLevelDB extends LevelDB{
	private const HEIGHTMAP_SIZE = 512;
	private const COPY_PREVIOUS = 127;

	public static function install(WorldProviderManager $manager) : void{
		$entry = new WritableWorldProviderManagerEntry(
			LevelDB::isValid(...),
			fn(string $path, \Logger $logger) => new self($path, $logger),
			LevelDB::generate(...)
		);
		$manager->addProvider($entry, "leveldb", true);
		$manager->setDefault($entry);
	}

	public function loadChunk(int $chunkX, int $chunkZ) : ?LoadedChunkData{
		try{
			return parent::loadChunk($chunkX, $chunkZ);
		}catch(CorruptedChunkException $e){
			if(!str_contains($e->getMessage(), "has no previous palette to copy from") || !$this->repairBiomes($chunkX, $chunkZ)){
				throw $e;
			}
			$this->logger->debug("Repaired 3D biome data of chunk x=$chunkX z=$chunkZ");
			return parent::loadChunk($chunkX, $chunkZ);
		}
	}

	private function repairBiomes(int $chunkX, int $chunkZ) : bool{
		$key = LevelDB::chunkIndex($chunkX, $chunkZ) . ChunkDataKey::HEIGHTMAP_AND_3D_BIOMES;
		$data = $this->db->get($key);
		if($data === false || strlen($data) <= self::HEIGHTMAP_SIZE || (ord($data[self::HEIGHTMAP_SIZE]) >> 1) !== self::COPY_PREVIOUS){
			return false;
		}
		//palette header with 0 bits per block = one biome for the whole sub-chunk, followed by that biome's ID
		$singleBiome = "\x00" . Binary::writeLInt(BiomeIds::PLAINS);
		$this->db->put($key, substr($data, 0, self::HEIGHTMAP_SIZE) . $singleBiome . substr($data, self::HEIGHTMAP_SIZE + 1));
		return true;
	}
}
