<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

use function App\Foundation\Helpers\{ app };

function name_format( string $a ): string
{
	if ( empty ( $string = trim ( $a ) ) )
	{
		return $string;
	}
	
	return mb_ucfirst( mb_strtolower( $string ) );
}