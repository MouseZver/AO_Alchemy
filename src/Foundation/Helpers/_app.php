<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

use Nouvu\Framework\Foundation\Application;

function app( Application $data = null ): Application
{
	static $app;
	
	return $app ??= $data;
}