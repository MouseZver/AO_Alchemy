<?php

declare ( strict_types = 1 );

namespace App\Database\Facade\SQLite;

use App\Foundation\Application;
use App\Database\Facade\LermaDatabase;
use App\Database\Traits AS DatabaseTrait;
use Nouvu\Framework\Component\Database\DatabaseInterface;
use Nouvu\Database\{ Lerma, LermaStatement, DriverEnum };

use function App\Foundation\Helpers\{ config };

final class LermaFacade extends LermaDatabase implements DatabaseInterface
{
	private Lerma $connect;
	
	private LermaStatement $statement;

	use DatabaseTrait\All,
		DatabaseTrait\Count,
		DatabaseTrait\Execute,
		DatabaseTrait\Get,
		DatabaseTrait\Id,
		DatabaseTrait\Prepare,
		DatabaseTrait\Query;
	
	public function __construct ( 
		private readonly Application $app 
	)
	{
		$this -> connect = Lerma :: create( driver: DriverEnum :: SQLite3 )
	        -> setFile( $app -> kernel -> getDataDir() . DIRECTORY_SEPARATOR . config( 'database.file' ) )
	        -> getLerma();
		
		$this -> connect -> connect() -> get() -> enableExceptions( true );
	}
}