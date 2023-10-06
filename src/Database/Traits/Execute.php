<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait Execute
{
	public function execute( array $data ): void
	{
		$this -> connect -> execute( $data );
	}
}