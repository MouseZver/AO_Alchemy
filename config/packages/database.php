<?php

use App\Database\Facade\SQLite\LermaFacade;
use Nouvu\Framework\Component\Database\DatabaseManager;

use function App\Foundation\Helpers\{ app, config, container };

return [
	\Database\Instance :: class => fn() => new DatabaseManager( app() ),
	\Database :: class => static function ()
	{
		config() -> set( 'database.class', LermaFacade :: class );
		
		return container( \Database\Instance :: class );
	},
];