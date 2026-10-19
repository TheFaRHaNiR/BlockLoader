<?php

declare(strict_types=1);

namespace ApexGaming\BlockLoader\block;

enum SeagrassType{
	case DEFAULT;
	case DOUBLE_TOP;
	case DOUBLE_BOT;

	public function getStateValue() : string{
		return match($this){
			self::DEFAULT => "default",
			self::DOUBLE_TOP => "double_top",
			self::DOUBLE_BOT => "double_bot",
		};
	}
}
