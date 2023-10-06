<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait Id
{
	public function id(): int
	{
		return $this -> connect -> InsertID();
	}
}