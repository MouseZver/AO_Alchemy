<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait Prepare
{
	public function prepare( string | array $sql, array $data ): void
	{
		$this -> statement = $this -> connect -> prepare( ...\func_get_args() );
	}
}