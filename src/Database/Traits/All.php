<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait All
{
	public function all( int $mode, callable | string $argument = null ): iterable
	{
		return $this -> statement -> fetchAll( $this -> getMode( $mode ), $argument );
	}
}