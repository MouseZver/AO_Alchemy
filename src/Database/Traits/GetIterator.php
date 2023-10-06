<?php

declare ( strict_types = 1 );

namespace App\Database\Traits;

trait GetIterator
{
	public function getIterator(): \Traversable
	{
		return $this -> statement;
	}
}