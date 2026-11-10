<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block\generic;

use pocketmine\block\Flowable;

/**
 * No collision: plants, portals.
 */
class GenericFlowable extends Flowable{
	use RawStatesTrait;
}
