<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait Count
{
	public function count(): int
	{
		return $this -> statement -> rowCount();
	}
}