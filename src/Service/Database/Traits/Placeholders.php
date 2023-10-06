<?php

declare ( strict_types = 1 );

namespace App\Service\Database\Traits;

trait Placeholders
{
	protected static function createStringFromPlaceholders( int $count ): string
	{
		return trim ( str_repeat ( '?,', $count ), ',' );
	}
}