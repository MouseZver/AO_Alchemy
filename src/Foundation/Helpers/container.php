<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

use function App\Foundation\Helpers\{ app };

function container( string $offset = null ): mixed
{
	if ( is_null ( $offset ) )
	{
		return app() -> container;
	}
	
	return app() -> container -> get( $offset );
}