<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

use Nouvu\Framework\Component\Database\DatabaseManager;

use function App\Foundation\Helpers\{ app };

function database(): DatabaseManager
{
	return app() -> database;
}