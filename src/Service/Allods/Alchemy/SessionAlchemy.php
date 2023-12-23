<?php

declare ( strict_types = 1 );

namespace App\Service\Allods\Alchemy;

use App\Service\Allods\Exception\AlchemyException;
use Symfony\Component\Console\Helper\{ Table, TableCell, TableSeparator };
use Symfony\Component\Console\Output\OutputInterface;

use function App\Foundation\Helpers\{ app };

class SessionAlchemy
{
	private string $sessionFile;
	private array $sessions = [];
	
	public function __construct ( string $name )
	{
		$this -> sessionFile = app() -> kernel -> getTempDir() . "/{$name}.session";
	}
	
	public function create(): void
	{
		if ( ! file_exists ( $this -> sessionFile ) )
		{
			file_put_contents ( $this -> sessionFile, '[]' );
		}
		else 
		{
			$sessions = json_decode ( file_get_contents ( $this -> sessionFile ), true );
			
			if ( json_last_error() !== JSON_ERROR_NONE || ! is_array ( $sessions ) )
			{
				file_put_contents ( $this -> sessionFile, '[]' );
				
				throw new AlchemyException( 'Сохраненные сессии были сброшены из-за json ошибки' );
			}
			
			$this -> sessions = $sessions;
		}
	}
	
	public function empty(): bool
	{
		return empty ( $this -> sessions );
	}
	
	public function view( OutputInterface $output ): void
	{
		$rows = function (): iterable
		{
			foreach ( $this -> sessions AS $id => [ 
				'potionsList' => $potionsList, 
				'countReaction' => $countReaction,
				'sorting' => $sorting
			] )
			{
				yield from [
					[ new TableCell( 'ID Session: ' . ( $id + 1 ), [ 'colspan' => 2 ] ) ],
					new TableSeparator(),
					...array_map ( 
						fn ( array $o ): array => [ 'Выбранное зелье:', sprintf ( '(%d) %s', $o['level'], $o['name'] ) ],
						$potionsList
					),
					[ 'Кол-во реакций:', $countReaction ],
					[ 'Оптимизированная сортировка:', ( $sorting ? 'Yes' : 'No' ) ]
				];
			}
		};
		
		$table = new Table( $output );
		$table 
			-> setHeaders( [ 'Задание', 'Значение' ] ) 
			-> setRows( iterator_to_array ( $rows() ) );
		$table -> render();
	}
	
	public function getData( int $id ): array
	{
		return $this -> sessions[$id] ?? throw new AlchemyException( 'Отсутствует сессия с номером: ' . ( $id + 1 ) );
	}
	
	public function save( array $data ): void
	{
		$this -> sessions[] = $data;
		
		if ( count ( $this -> sessions ) > 5 )
		{
			array_shift ( $this -> sessions );
		}
		
		file_put_contents ( $this -> sessionFile, json_encode ( $this -> sessions, 480 ) );
	}
}