<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait Query
{
	public function query( string | array $sql ): void
	{
		$this -> statement = $this -> connect -> query( $sql );
	}
}