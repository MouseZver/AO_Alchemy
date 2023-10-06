<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

use function App\Foundation\Helpers\{ app };

function config( string $offset = null, mixed $default = null ): mixed
{
	if ( is_null ( $offset ) )
	{
		return app() -> repository;
	}
	
	return app() -> repository -> get( $offset, $default );
}