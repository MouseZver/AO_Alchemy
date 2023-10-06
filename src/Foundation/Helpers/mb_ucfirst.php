<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

use function App\Foundation\Helpers\{ app };

function mb_ucfirst( string $string ): string
{
	$string = mb_strtoupper ( mb_substr ( $string, 0, 1 ) ) . mb_substr ( $string, 1 );

	return $string;
}