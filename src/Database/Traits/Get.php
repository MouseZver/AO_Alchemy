<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait Get
{
	public function get( int $mode, callable | string $argument = null ): mixed
	{
		return $this -> statement -> fetch( $this -> getMode( $mode ), $argument );
	}
}