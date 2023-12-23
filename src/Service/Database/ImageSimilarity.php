<?php

declare ( strict_types = 1 );

namespace App\Service\Database;

use Nouvu\Framework\Component\Database\StatementInterface;

use function App\Foundation\Helpers\{ config, database };

class ImageSimilarity
{
	private const 
        DATA_TABLE = 'imageSim_data',
        NAMES_TABLE = 'imageSim_name';
	
	public static function createTables(): void
	{
		database() -> query( [ '
			CREATE TABLE IF NOT EXISTS %s(
				id INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT,
				series TEXT, 
				name TEXT
			)', config( 'database.prefix' ) . self :: NAMES_TABLE ] );
		
		database() -> query( [ '
			CREATE TABLE IF NOT EXISTS %s(
				id INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT,
				series TEXT, 
				percent TEXT,
				hash TEXT, 
				category TEXT, 
				data TEXT
			)', config( 'database.prefix' ) . self :: DATA_TABLE ] );
	}
	
	public static function findDataByCategory( string $category ): StatementInterface
	{
		return database() -> query( [ 'SELECT series, data FROM %s WHERE category = "%s"', config( 'database.prefix' ) . self :: DATA_TABLE, $category ] );
	}
	
	public static function insertImageData( array $data, string $hash, string $category, string $points ): void
	{
		database() -> prepare( [ 'INSERT INTO %s( series, percent, hash, category, data ) VALUES ( ?, ?, ?, ?, ? )', config( 'database.prefix' ) . self :: DATA_TABLE ], [
			$data['series'],
			$data['percent'],
			$hash,
			$category,
			$points
		] );
	}
	
	public static function insertNamesData( string $series, string $text ): void
	{
		database() -> prepare( [ 'INSERT INTO %s( series, name ) VALUES ( ?, ? )', config( 'database.prefix' ) . self :: NAMES_TABLE ], [
			$series,
			$text
		] );
	}
	
	public static function findNameByHashAndCategory( string $hash, string $category ): ?string
	{
		return database() -> prepare( [ '
	        SELECT t1.name 
	        FROM %s as t1 
	        JOIN %s as t2 
	            ON t1.series = t2.series 
	        WHERE 
	            t2.hash = ? AND t2.category = ?
	    ', config( 'database.prefix' ) . self :: NAMES_TABLE, config( 'database.prefix' ) . self :: DATA_TABLE ], [
			$hash, 
			$category
	    ] ) 
		-> get( StatementInterface :: FETCH_COLUMN );
	}

	public static function findIdByHash( string $hash ): ?int
	{
		return database() -> query( [ 'SELECT id FROM %s WHERE hash = "%s"', config( 'database.prefix' ) . self :: DATA_TABLE, $hash ] )
			-> get( StatementInterface :: FETCH_COLUMN );
	}
}