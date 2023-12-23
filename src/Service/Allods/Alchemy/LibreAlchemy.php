<?php

declare ( strict_types = 1 );

namespace App\Service\Allods\Alchemy;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use App\Neuronet\ImageSimilarity;
use App\Service\Allods\Exception\{ AlchemyException, AlchemyReactionEmpty, AlchemySuccess };
use App\Service\Database\Alchemy AS DatabaseAlchemy;
use App\Foundation\Helpers\name_format;

use function App\Foundation\Helpers\{ app, container, config, name_format, confirm };

final class LibreAlchemy
{
	private array $actions = [
		//'OnAlchemyStarted', // LibreAlchemy: Приветствую O_O!
		//'OnAlchemyItemPlaced', // возможно, есть рецепты: N шт.
		'OnAlchemyReactionFinished', // 103,4,0,0,0,0,Ледяной эликсир марафонца
	];

	private array $ingredients = [];

	private ?int $OnAlchemyItemPlaced = null;

	private ImageSimilarity $imSim;
	private int $countReaction = 0, 
		$countSuccessReaction = 0, 
		$countFailedReaction = 0;
	private bool $sorting;
	private string $modsPath;

	public function __construct ( 
		private readonly array $potionsList = [], 
		private readonly array $aspects = [] 
	)
	{
		$imagesDir = app() -> kernel -> getProjectDir() . '/var/images';
		
		if ( ! file_exists ( $imagesDir ) )
		{
			mkdir ( $imagesDir, 0777, true );
		}
	}

	public static function create(
		ImageSimilarity $imSim,
		array $potionsList,
		array $aspects,
		int $countReaction,
		bool $sorting,
		string $modsPath
	): self
	{
		$alchemy = new static( potionsList: $potionsList, aspects: $aspects );
		
		return $alchemy -> countReaction( $countReaction )
			-> sorting( $sorting )
			-> modsLog( $modsPath )
			-> neuralNetworkImages( $imSim );
	}
	
	public function countReaction( int $count ): self
	{
		$this -> countReaction = $count;
		
		return $this;
	}

	public function sorting( bool $flag ): self
	{
		$this -> sorting = $flag;
		
		return $this;
	}

	public function modsLog( string $path ): self
	{
		$this -> modsPath = $path;
		
		return $this;
	}

	public function neuralNetworkImages( ImageSimilarity $imSim ): self
	{
		$this -> imSim = $imSim;
		
		return $this;
	}

	public function build( SymfonyStyle $io, OutputInterface $output ): void
	{
		try
		{
			$this -> isModsLog();
			
			while ( true )
			{
				$this -> checkUIAlchemy();
				
				$this -> checkReady();
				
				$io -> info( 'UI Интерфейс проверен и готов.' );
				
				$ingredients = $this -> getIngredients();
				$this -> checkIngredients( $ingredients );
				$save = $this -> saveIngredients( $ingredients );
				
				$io -> info( 'Ингредиенты проверены' . ( $save ? 'и зафиксированы.' : '.' ) );
				
				$table = new Table( $output );
				
				$table 
					-> setHeaders( [ '#', 'NAME' ] ) 
					-> setRows( array_map ( 
						fn ( int $k, string $name ): array => [ $k + 1, $name ],
						array_keys ( $ingredients ),
						$ingredients
					) );
				
				$table -> render();
				
				//$count = $this -> countPossiblePotions();
				
				try
				{
					$io -> info( 'Приступаю к варке' );
					
					$result = $this -> preparePotion( $io );
					
					$io -> info( 'Варим: ' . $result['name'] );
					
					$table = new Table( $output );
					
					$table 
						-> setHeaders( [ 'LVL', 'NAME', 'SCROLLDATA' ] )
						-> setRows( $result['list'] );
					
					$table -> render();
					
					$io -> info( 'Сопоставляю аспекты' );
					
					$this -> scrollingAspects( $result );
					
					$this -> selectPotionResult( $result );
					
					$io -> success( 'Успешно приготовлено зелье: ' . $result['name'] );
					
					$this -> processSuccess();
				}
				catch ( AlchemyReactionEmpty $r )
				{
					$io -> warning( $r -> getMessage() );
					
					$this -> missSelectResult();
					
					$this -> processFailed();
				}
				
				sleep ( 2 );
			}
		}
		catch ( AlchemyException $e )
		{
			$io -> error( $e -> getMessage() );
		}
		catch ( AlchemySuccess $s )
		{
			$io -> success( $s -> getMessage() );
		}
		
		$this -> end( $io, $output );
	}

	public function isModsLog(): bool
	{
		return file_exists ( $this -> modsPath ) ?: 
			throw new AlchemyException( 'Файл mods.txt не найден. Убедитесь в настройках что включено логирование аддонов.' );
	}

	protected function readLastDataLibreAlchemy(): array
	{
		$this -> isModsLog();
		
		$content = trim ( file_get_contents ( $this -> modsPath ) );
		
		preg_match_all (
			'/\[(?<time>\d{2}:\d{2}:\d{2})\]Info: addon LibreAlchemy\(\d+\): (?<action>.*):(?<data>.*)/',
			mb_convert_encoding ( $content, 'UTF-8', 'Windows-1251' ),
			$matches,
			PREG_SET_ORDER
		);
		
		if ( empty ( $matches ) )
		{
			throw new AlchemyException( sprintf ( 'Отсутсвует информация об LibreAlchemy в %s.', basename ( $this -> modsPath ) ) );
		}
		
		[ 'time' => $time, 'action' => $action, 'data' => $data ] = end ( $matches );
		
		$expired = strtotime ( $time . ' + 1 MIN' ) < strtotime ( 'NOW' );
		
		$notInList = ! in_array ( $action, $this -> actions, true );
		
		if ( $expired || $notInList )
		{
			throw new AlchemyException( 'Внешние данные не прошли проверку: ' . json_encode ( compact ( 'time', 'action', 'data' ), 480 ) );
		}
		
		$this -> clearModsLog();
		
		return compact ( 'action', 'data' );
	}

	private function clearModsLog(): void
	{
		file_put_contents ( $this -> modsPath, '' );
	}

	public function checkUIAlchemy(): bool
	{
		$im = ScreenUILibreAlchemy :: UIInterface();
		
		InterfaceRecognition :: UIInterface( im: $im, createData: config( 'allods.LibreAlchemy.learn.checkUIAlchemy' ) );
		
		$bool = $this -> imSim -> getResult( 0 ) === '{start}';
		
		$this -> imSim -> clean();
		
		return $bool ?: throw new AlchemyException( 'Не распознан UI интерфейс. Перезапустите LibreAlchemy' );
	}

	public function checkReady(): bool
	{
		$im = ScreenUILibreAlchemy :: OnAlchemyReactionFinished();
		
		InterfaceRecognition :: UIInterface( im: $im, createData: config( 'allods.LibreAlchemy.learn.checkReady' ) );
		
		$bool = $this -> imSim -> getResult( 0 ) === '{OnAlchemyReactionFinished:empty}';
		
		$this -> imSim -> clean();
		
		return $bool ?: throw new AlchemyException( 'Интерфейс не готов: {OnAlchemyReactionFinished:empty}' );
	}

	public function getIngredients(): array
	{
		return $this -> ingredients;
	}

	public function checkIngredients( array &$ingredients ): void
	{
		$im = ScreenUILibreAlchemy :: ingredients();
		
		InterfaceRecognition :: ingredients( im: $im, createData: config( 'allods.LibreAlchemy.learn.ingredients' ) );
		
		// [ 'componentName', ... ]
		$components = $this -> imSim -> getResults();
		
		$this -> imSim -> clean();
		
		if ( empty ( $ingredients ) )
		{
			$aspectsList = DatabaseAlchemy :: findAspectsByComponents( ...$components );
			
			foreach ( $this -> aspects AS $key => $aspectName )
			{
				$flag = true;
				
				foreach ( $aspectsList AS &$aspects )
				{
					$aspects = array_unique ( $aspects );
					
					if ( $intersect = array_intersect ( $aspects, [ $aspectName ] ) )
					{
						$flag = false;
						
						$intersectKey = array_key_first ( $intersect );
						
						unset ( $aspects[$intersectKey] );
						
						break;
					}
				}
				
				if ( $flag )
				{
					error_log ( json_encode ( $aspects, 480 ) );
					
					throw new AlchemyException( sprintf ( 'Компонент %s не имеет аспект %s', $components[$key], $aspectName ) );
				}
				
				$ingredients[$key] = name_format ( $components[$key] );
			}
		}
		else if ( $unknown = array_diff ( $components, $ingredients ) )
		{
			error_log ( json_encode ( compact ( 'components', 'ingredients', 'unknown' ), 480 ) );
			
			throw new AlchemyException( 'Найдено расхождение зафиксированных компонентов: ' . implode ( ', ', $components ) );
		}
	}

	public function saveIngredients( array $ingredients ): bool
	{
		$bool = ( bool ) array_intersect ( $ingredients, $this -> ingredients );
		
		$this -> ingredients = $ingredients;
		
		return $bool;
	}
	
	protected function isAction( array $data, string $action ): bool
	{
		if ( $data['action'] === $action )
		{
			return true;
		}
		
		throw new AlchemyException( sprintf ( 'Неожиданный шаг вне очереди %s. Данные: %s', $data['action'], $data['data'] ) );
	}
	
	public function preparePotion( SymfonyStyle $io ): array
	{
		$this -> clearModsLog();
		
		DirectInputMouse :: preparePotion();
		
		$data = $this -> waitData( $io );
		
		$this -> isAction( $data, 'OnAlchemyReactionFinished' );
		
		if ( $data['data'] === '{empty}' )
		{
			throw new AlchemyReactionEmpty( 'Тут ничего, кроме бормотухи.' );
		}
		
		// 103,0,0,-1,0,0,Ледяной эликсир марафонца|98,0,1,-1,-1,-2,Райский эликсир марафонца
		
		$result = iterator_to_array ( $this -> normalizedData( $data['data'] ) );
		
		/* [
			[ 'lvl', 'name', 'scrollData' => [ 1,2,3,4,5 ] ],
			[ 'lvl', 'name', 'scrollData' => [ 1,2,3,4,5 ] ],
		] */
		
		if ( $this -> sorting )
		{
			usort ( $result, static function ( array $a, array $b ): int
			{
				$sumData = fn ( array $data ): int => array_sum ( array_map ( 'abs', $data ) );
				
				return $sumData( $a['scrollData'] ) <=> $sumData( $b['scrollData'] );
			} );
		}
		
		foreach ( $result AS $row )
		{
			foreach ( $this -> potionsList AS $potion )
			{
				if ( $row['name'] === $potion -> name )
				{
					return [
						'list' => array_map ( static function ( array $data ): array
						{
							$data['scrollData'] = implode ( ',', $data['scrollData'] );
							
							return $data;
						}, 
						$result ),
						...$row
					];
				}
			}
		}
		
		throw new AlchemyReactionEmpty( 'Требуемые зелья по списку отсутствуют.' );
	}

	private function waitData( SymfonyStyle $io ): array
	{
		$max = 5;
		$i = 0;
		
		while ( true )
		{
			try
			{
				return $this -> readLastDataLibreAlchemy();
			}
			catch ( AlchemyException )
			{
				if ( $i == $max && confirm( $io, 'Продолжить выполнение операции ?' ) )
				{
					$i = 0;
				}
				else if ( $i == $max )
				{
					throw new AlchemyException( 'Операция приостановлена.' );
				}
				
				$io -> writeln( 'Ожидаем...' );
				
				$i++;
				
				sleep ( 4 );
			}
		}
	}

	private function normalizedData( string $data ): iterable
	{
		foreach ( explode ( '|', $data ) AS $splitData )
		{
			[ $level, $s1, $s2, $s3, $s4, $s5, $name ] = explode ( ',', $splitData );
			
			yield [ 
				'lvl' => ( int ) $level, 
				'name' => $name, 
				'scrollData' => [ ( int ) $s1, ( int ) $s2, ( int ) $s3, ( int ) $s4, ( int ) $s5 ] 
			];
		}
	}

	public function scrollingAspects( array $data )
	{
		foreach ( $data['scrollData'] AS $k => $action )
		{
			if ( ! empty ( $action ) )
			{
				for ( $i = abs ( $action ); $i > 0; $i-- )
				{
					$string = sprintf ( 'allods.LibreAlchemy.clickArea.scrollingAspects.%d.%d', $k, $action <=> 0 );
					
					DirectInputMouse :: leftClick( ...config( $string ) );
					
					sleep ( 1 );
				}
			}
		}
	}

	public function selectPotionResult( array $data ): void
	{
		sleep ( 1 );
		
		$im = ScreenUILibreAlchemy :: OnAlchemyReactionFinished();
		
		InterfaceRecognition :: OnAlchemyReactionFinished( im: $im, createData: config( 'allods.LibreAlchemy.learn.reactionFinished' ) );
		
		$result = $this -> imSim -> getResults();
		
		$this -> imSim -> clean();
		
		foreach ( $result AS $k => $potionName )
		{
			if ( $potionName === $data['name'] )
			{
				$string = sprintf ( 'allods.LibreAlchemy.clickArea.result.%d', $k );
				
				DirectInputMouse :: leftClick( ...config( $string ) );
				
				return;
			}
		}
		
		throw new AlchemyException( 'Что-то пошло не так: ' . json_encode ( compact ( 'result', 'data' ), 480 ) );
	}

	public function missSelectResult(): void
	{
		$string = sprintf ( 'allods.LibreAlchemy.clickArea.result.%d', 0 );
		
		DirectInputMouse :: leftClick( ...config( $string ) );
	}

	private function isProcessEnd(): void
	{
		$this -> countReaction--;
		
		if ( empty ( $this -> countReaction ) )
		{
			throw new AlchemySuccess( 'Завершение работы.' );
		}
	}

	public function processSuccess(): void
	{
		$this -> countSuccessReaction++;
		
		$this -> isProcessEnd();
	}

	public function processFailed(): void
	{
		$this -> countFailedReaction++;
		
		$this -> isProcessEnd();
	}

	public function end( SymfonyStyle $io, OutputInterface $output ): void
	{
		$table = new Table( $output );
		$table 
			-> setHeaders( [ 'Описание', 'Значение' ] ) 
			-> setRows( [
				[ 'Кол-во успешных реакций:', $this -> countSuccessReaction ],
				[ 'Кол-во неудачных реакций:', $this -> countFailedReaction ],
				[ 'Всего реакций:', ( $this -> countSuccessReaction + $this -> countFailedReaction ) ]
			] );
		
		$table -> render();
		
		container( \Arduino\Mouse :: class ) -> send ( 'ping' );
		
		$io -> warning( 'Процесс приготовления остановлен.' );
	}
}