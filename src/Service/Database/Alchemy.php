<?php

declare ( strict_types = 1 );

namespace App\Service\Database;

use Nouvu\Framework\Component\Database\StatementInterface;

use function App\Foundation\Helpers\{ config, database };

class Alchemy
{
	private const 
        ASPECTS_TABLE = 'alchemy_aspects',
        COMPONENTS_TABLE = 'alchemy_components',
        POTIONS_TABLE = 'alchemy_potions';
	
	use Traits\Placeholders;
	
	public static function createTables(): void
	{
		database() -> query( [ '
			CREATE TABLE IF NOT EXISTS %s(
				id INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT,
				name TEXT UNIQUE NOT NULL
			)', config( 'database.prefix' ) . self :: ASPECTS_TABLE ] );
    
        database() -> query( [ '
            CREATE TABLE IF NOT EXISTS %s(
                id INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL, 
                a1 TEXT NOT NULL, 
                a2 TEXT NOT NULL, 
                a3 TEXT NOT NULL,
                a4 TEXT NOT NULL
            )', config( 'database.prefix' ) . self :: COMPONENTS_TABLE ] );

        database() -> query( [ '
            CREATE TABLE IF NOT EXISTS %s(
                id INTEGER UNIQUE PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL, 
                level INTEGER NOT NULL, 
                min INTEGER NOT NULL, 
                max INTEGER NOT NULL,
                a1 TEXT NOT NULL,
                a2 TEXT NOT NULL,
                a3 TEXT,
                a4 TEXT,
                a5 TEXT
            )', config( 'database.prefix' ) . self :: POTIONS_TABLE ] );
	}

    public static function addAspect( string $name ): int
    {
        $a = database() -> prepare( 
            [ 'INSERT INTO %s( name ) VALUES ( ? )', config( 'database.prefix' ) . self :: ASPECTS_TABLE ], 
            [ $name ]
        );

        return $a -> id();
    }

    public static function findAspectByName( string $name ): ?object
    {
        return database() -> prepare ( 
            [ 'SELECT * FROM %s WHERE name = ?', self :: ASPECTS_TABLE ],
            [ $name ]
        ) -> get( StatementInterface :: FETCH_OBJ );
    }
    
    public static function findPotionById( int $id ): ?object
    {
        return database() 
            -> query ( [ '
            SELECT t1.id, t1.name, t1.level, t1.min, t1.max, t2.name AS a1, t3.name AS a2, t4.name AS a3, t5.name AS a4, t6.name AS a5
            FROM %s AS t1
            JOIN %2$s AS t2 ON t2.id = t1.a1
            JOIN %2$s AS t3 ON t3.id = t1.a2
            LEFT JOIN %2$s AS t4 ON t4.id = t1.a3
            LEFT JOIN %2$s AS t5 ON t5.id = t1.a4
            LEFT JOIN %2$s AS t6 ON t6.id = t1.a5
            WHERE t1.id = %3$d
        ', self :: POTIONS_TABLE, self :: ASPECTS_TABLE, $id ] ) 
            -> get( StatementInterface :: FETCH_OBJ );
    }

    public static function addComponent( string $name, int ...$aspects ): int
    {
        $a = database() -> prepare( 
            [ 'INSERT INTO %s( name, a1, a2, a3, a4 ) VALUES ( ?, ?, ?, ?, ? )', config( 'database.prefix' ) . self :: COMPONENTS_TABLE ], 
            [ $name, ...$aspects ]
        );

        return $a -> id();
    }

    public static function addPotion( string $name, string $level, string $min, string $max, ?int ...$aspects ): int
    {
        $a = database() -> prepare(
            [ "INSERT INTO %s( 'name', 'level', 'min', 'max', 'a1', 'a2', 'a3', 'a4', 'a5' ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? )", config( 'database.prefix' ) . self :: POTIONS_TABLE ],
            [ $name, ( int ) $level, ( int ) $min, ( int ) $max, ...$aspects ] 
        );

        return $a -> id();
    }

    public static function viewPotions(): array
    {
        return database() 
            -> query( [ '
                SELECT t1.id, t1.name, t1.level, t1.min, t1.max, t2.name AS a1, t3.name AS a2, t4.name AS a3, t5.name AS a4, t6.name AS a5
                FROM %s AS t1
                JOIN %2$s AS t2 ON t2.id = t1.a1
                JOIN %2$s AS t3 ON t3.id = t1.a2
                LEFT JOIN %2$s AS t4 ON t4.id = t1.a3
                LEFT JOIN %2$s AS t5 ON t5.id = t1.a4
                LEFT JOIN %2$s AS t6 ON t6.id = t1.a5
            ', self :: POTIONS_TABLE, self :: ASPECTS_TABLE ] )
            -> all( StatementInterface :: FETCH_NUM );
    }
    
    public static function viewAspects(): array
    {
        return database()
            -> query( 'SELECT * FROM ' . self :: ASPECTS_TABLE )
            -> all( StatementInterface :: FETCH_NUM );
    }
    
    public static function viewComponents(): array
    {
        return database() 
            -> query( [ '
                SELECT t1.id, t1.name, t2.name AS a1, t3.name AS a2, t4.name AS a3, t5.name AS a4
                FROM %s AS t1
                JOIN %2$s AS t2 ON t2.id = t1.a1
                JOIN %2$s AS t3 ON t3.id = t1.a2
                JOIN %2$s AS t4 ON t4.id = t1.a3
                JOIN %2$s AS t5 ON t5.id = t1.a4
            ', self :: COMPONENTS_TABLE, self :: ASPECTS_TABLE ] )
            -> all( StatementInterface :: FETCH_NUM );
    }
    
    public static function findAspectsByComponents( string ...$components ): ?array
    {
		return database() 
            -> prepare( [ '
                SELECT t2.name AS a1, t3.name AS a2, t4.name AS a3, t5.name AS a4
                FROM %s AS t1
                JOIN %2$s AS t2 ON t2.id = t1.a1
                JOIN %2$s AS t3 ON t3.id = t1.a2
                JOIN %2$s AS t4 ON t4.id = t1.a3
                JOIN %2$s AS t5 ON t5.id = t1.a4
                WHERE t1.name IN( %3$s )
            ', self :: COMPONENTS_TABLE, self :: ASPECTS_TABLE, self :: createStringFromPlaceholders( \func_num_args () ) ], $components )
            -> all( StatementInterface :: FETCH_NUM );
    }
}