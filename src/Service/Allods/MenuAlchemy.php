<?php

declare ( strict_types = 1 );

namespace App\Service\Allods;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use App\Service\Database\Alchemy AS DatabaseAlchemy;
use App\Service\Allods\LibreAlchemy;
use App\Service\Allods\Exception\AlchemyException;
use App\Neuronet\ImageSimilarity;

//use Nouvu\Database\Exception\LermaException AS DatabaseException;
use App\Foundation\Helpers\{ name_format, interatorToBlackWhitePixels };

use function App\Foundation\Helpers\{ app, config, container, name_format };

class MenuAlchemy
{
    private array $menu = [
        [ 'name' => 'Добавить аспект', 'action' => 'addAspect' ],
        [ 'name' => 'Вывести таблицу аспектов', 'action' => 'viewAspects' ],
        [ 'name' => 'Добавить компонент', 'action' => 'addComponent' ],
        [ 'name' => 'Вывести таблицу компонентов', 'action' => 'viewComponents' ],
        [ 'name' => 'Добавить зелье', 'action' => 'addPotion' ],
        [ 'name' => 'Вывести таблицу зелий', 'action' => 'viewPotions' ],
        [ 'name' => 'Запустить тестирование', 'action' => 'runTest' ],
        [ 'name' => 'Запустить обучение', 'action' => 'runLearn' ],
        [ 'name' => 'Запустить Бота Алхимии', 'action' => 'runBot' ],
    ];
	
	public function __construct (
        private SymfonyStyle $io,
        private InputInterface $input,
        private OutputInterface $output
    )
    {
		DatabaseAlchemy :: createTables();
	}
    
	public function run(): void
	{
		$this -> io -> info( 'Start -> Allods Alchemy' );
		
		$menu = $this -> getMenuList( array_column ( $this -> menu, 'name' ) );
		
        $this -> io -> definitionList( ...$menu );
		
        $id = $this -> io -> ask( 'Номер процедуры' );

        if ( isset ( $this -> menu[$id - 1] ) )
        {
            $this -> {$this -> menu[$id - 1]['action']}( $this -> input, $this -> output );
        }
        else
        {
            $this -> io -> error( "Процедура с номером \"{$id}\" не существует." );
        }
	}
	
	public function addAspect(): void
    {
        $name = $this -> ask( 'Введите название Аспекта.' );
		
        try
        {
            DatabaseAlchemy :: addAspect( $name );

            $this -> io -> success( "Аспект <{$name}> добавлен" );
        }
        catch ( \Exception $e )
        {
            $this -> io -> error( $e -> getMessage() );
        }
		
		$this -> addAspect();
    }

    public function addComponent(): void
    {
        $askList = [
            'name' => 'Введите название Компонента/Цветка.',
            'aspects' => 'Введите 4 аспекта через запятую.',
        ];

        $storage = $this -> askList( $askList );

        try
        {
            $aspects = iterator_to_array ( $this -> validationAspects( 4, 4, $storage['aspects'] ) );
            
            $name = name_format( $storage['name'] );

            DatabaseAlchemy :: addComponent( $name, ...$aspects );

            $this -> io -> success( "Компонент <{$name}> добавлен." );
        }
        catch ( \Exception $e )
        {
            $this -> io -> error( $e -> getMessage() );
        }

		$this -> addComponent();
    }
    
    public function viewComponents(): void
    {
        $table = new Table( $this -> output );

        $table 
            -> setHeaders( [ 'ID', 'NAME', 'A1', 'A2', 'A3', 'A4' ] ) 
            -> setRows( DatabaseAlchemy :: viewComponents() );

        $table -> render();
    }

    public function addPotion(): void
    {
        $askList = [
            'name' => 'Введите название Зелья.',
            'aspects' => 'Введите 5 Аспектов через запятую. Требуемое минимальное кол-во: 2',
            'min' => 'Стандартное количество Зелий за варку.',
            'level' => 'Навык Зелья.',
        ];

        $storage = $this -> askList( $askList );

        try
        {
            $aspects = iterator_to_array ( $this -> validationAspects( 5, 2, $storage['aspects'] ) );

            unset ( $storage['aspects'] );

            $storage['name'] = name_format( $storage['name'] );

            $storage['max'] = ( string ) ( $storage['min'] * 3 );

            DatabaseAlchemy :: addPotion( ...$storage, ...$aspects );

            $this -> io -> success( "Зелье <{$storage['name']}> добавлено." );
        }
        catch ( \Exception $e )
        {
            $this -> io -> error( $e -> getMessage() );
        }

		$this -> addPotion();
    }

    public function viewPotions(): void
    {
        $table = new Table( $this -> output );

        $table 
            -> setHeaders( [ 'ID', 'NAME', 'LEVEL', 'MIN', 'MAX', 'A1', 'A2', 'A3', 'A4', 'A5' ] ) 
            -> setRows( DatabaseAlchemy :: viewPotions() );

        $table -> render();
    }
    
    public function viewAspects(): void
    {
        $table = new Table( $this -> output );
        
        $table 
            -> setHeaders( [ 'ID', 'NAME' ] ) 
            -> setRows( DatabaseAlchemy :: viewAspects() );
        
        $table -> render();
    }
    
    public function runBot(): void
    {
        $this -> viewPotions();
        
        $askList = [
            'id' => 'Введите id Зелья.',
            'countReaction' => 'Минимальное кол-во успешных итераций требуемое сварить.',
        ];
        
        [ 'id' => $id, 'countReaction' => $countReaction ] = $this -> askList( $askList );
        
        try
        {
            $potionsList = [];
            $potionsList[] = DatabaseAlchemy :: findPotionById( ( int ) $id ) ?: 
                    throw new \Exception( sprintf ( 'Введенное id:%d Зелья не найдено.', $id ) );
            $potionsList = $this -> addOtherPotion( $potionsList );
            
            $sorting = isset ( $potionsList[1] ) && $this -> io -> confirm( 'Оптимизировать выборку при варке зелий ?' );
            
            
            $table = new Table( $this -> output );
            $table 
                -> setHeaders( [ 'Задание', 'Значение' ] ) 
                -> setRows( [
                    ...array_map ( 
                        fn ( object $o ): array => [ 'Выбранное зелье:', sprintf ( '(%d) %s', $o -> level, $o -> name ) ],
                        $potionsList
                    ),
                    [ 'Кол-во реакций:', $countReaction ],
                    [ 'Оптимизированная сортировка:', ( $sorting ? 'Yes' : 'No' ) ]
                ] );
            
            $table -> render();
            
            
            if ( ! $this -> io -> confirm( 'Ready ?' ) )
            {
                return;
            }
            
            $i = config( 'allods.LibreAlchemy.startSeconds' );
            
            do
            {
                $this -> output -> writeln( $i ? "Запуск бота через {$i} секунд..." : 'Иницилизация...' );
                
                sleep ( 1 );
            }
            while ( $i-- );
            
            $this -> io -> newline();
            
            
            $libreAlchemy = LibreAlchemy :: create( 
                imSim: container( \NeuralNetworkImages :: class ),
                potionsList: $potionsList,
                aspects: array_values ( array_intersect_key ( ( array ) end ( $potionsList ), array_flip( [ 'a1', 'a2', 'a3', 'a4', 'a5' ] ) ) ),
                countReaction: ( int ) $countReaction,
                sorting: $sorting,
                modsPath: config( 'allods.LibreAlchemy.modsLogPath' )
            );
            
            $libreAlchemy -> build( $this -> io, $this -> output );
        }
        catch ( \Exception $e )
        {
            $this -> io -> error( $e -> getMessage() );
        }
        
        //app() -> nircmd -> win( 'hide', 'process', "AOgame.exe" );
        
        //$this -> runBot();
    }
    
    public function runTest(): void
    {
        $imSim = container( \NeuralNetworkImages :: class );
		
		$this -> io -> definitionList( ...$this -> getMenuList( [
			'Интерфейс (UIInterface)',
			'Ингредиенты (Ingredients)',
			'Результат варки (OnAlchemyReactionFinished)',
			'All'
		] ) );
		
        $id = $this -> io -> ask( 'Номер исполняемого метода' );
        
        $createData = $this -> io -> confirm( 'Сохранять обучаемые данные ?' );
		
		$fastStart = $this -> io -> confirm( 'Запустить без ожидания ?' );
        
        if ( ! $this -> io -> confirm( 'Ready ?' ) )
        {
            return;
        }
        
        $i = $fastStart ? 1 : config( 'allods.LibreAlchemy.startSeconds' );
        
        do
        {
            $this -> output -> writeln( $i ? "Запуск бота через {$i} секунд..." : 'Иницилизация...' );
                
            sleep ( 1 );
        }
        while ( $i-- );
		
		$facade = new class ( container( \NeuralNetworkImages :: class ), $createData )
		{
			public function __construct (
				private readonly ImageSimilarity $imSim,
				private readonly bool $createData
			)
			{}
			
			public function UIInterface(): array
			{
				$im = ScreenUILibreAlchemy :: UIInterface();
				
				InterfaceRecognition :: UIInterface( $im, $this -> createData );
				
				$result = $this -> imSim -> getResults();
				
				$this -> imSim -> clean();
				
				return [ 'UIInterface' => $result ];
			}
			
			public function ingredients(): array
			{
				$im = ScreenUILibreAlchemy :: ingredients();
				
				InterfaceRecognition :: ingredients( $im, $this -> createData );
				
				$result = $this -> imSim -> getResults();
				
				$this -> imSim -> clean();
				
				return [ 'ingredients' => $result ];
			}
			
			public function reactionFinished(): array
			{
				$im = ScreenUILibreAlchemy :: OnAlchemyReactionFinished();
				
				InterfaceRecognition :: OnAlchemyReactionFinished( $im, $this -> createData );
				
				$result = $this -> imSim -> getResults();
				
				$this -> imSim -> clean();
				
				return [ 'OnAlchemyReactionFinished' => $result ];
			}
			
			public function all(): array
			{
				return [
					...$this -> UIInterface(),
					...$this -> ingredients(),
					...$this -> reactionFinished()
				];
			}
		};
        
		$result = match( $id )
		{
			'1' => $facade -> UIInterface(),
			'2' => $facade -> ingredients(),
			'3' => $facade -> reactionFinished(),
			default => $facade -> all(),
		};
        
		$this -> io -> info( 'Результат:' );
		
        $this -> io -> error( json_encode ( $result, 480 ) );
    }
    
    private function addOtherPotion( array $potionsList ): array
    {
        while ( $this -> io -> confirm( 'Добавить второстипенное зелье ?' ) )
        {
            try
            {
                $id = $this -> ask( 'Введите id Зелья.' );
                
                $potionsList[] = DatabaseAlchemy :: findPotionById( ( int ) $id ) ?: 
                    throw new AlchemyException( sprintf ( 'Введенное id:%d Зелья не найдено.', $id ) );
            }
            catch ( AlchemyException $e )
            {
                $this -> io -> error( $e -> getMessage() );
            }
        }
        
        return $potionsList;
    }
    
    private function getMenuList( array $list ): array
    {
        return array_map ( static function ( int $key, string $text ): array
        {
            return [ ( $key + 1 ) => $text ];
        },
        array_keys ( $list ), $list );
    }

    private function ask( string $name ): string
    {
        while ( true )
		{
			$answer = $this -> io -> ask( $name . '  (Выход команда: exit)' );
			
			if ( $answer === 'exit' )
			{
				exit;
			}
			else if ( ! empty ( $answer ) )
			{
				return $answer;
			}
		}
    }

    private function askList( array $array ): array
    {
        $storage = [];
        
        foreach ( $array AS $k => $ask )
        {
            $storage[$k] = $this -> ask( $ask );
        }

        return $storage;
    }

    public function validationAspects( int $countAspect, int $request, string $aspects ): iterable
    {
        $segments = array_map ( name_format :: class, explode ( ',', trim ( $aspects, ',' ) ) );
        
        for ( $i = 0; $i < $countAspect; $i++ )
        {
            if ( isset ( $segments[$i] ) )
            {
                $aspect = DatabaseAlchemy :: findAspectByName( $segments[$i] );
                
                if ( empty ( $aspect ) )
                {
                    $this -> io -> warning( sprintf ( 'Аспект <%s> отсутствует в списке.', $segments[$i] ) );
                    
                    if ( $this -> io -> confirm( 'Желаете добавить в список ?' ) )
                    {
                        $id = DatabaseAlchemy :: addAspect( $segments[$i] );

                        $this -> io -> success( "Аспект <{$segments[$i]}> добавлен" );
                    }
                    else
                    {
                        throw new \LogicException( 'Предмет не добавлен.' );
                    }
                }
                
                yield $aspect ?-> id ?? $id ?? null;
            }
            else if ( $i >= $request )
            {
                yield null;
            }
            else
            {
                throw new \LogicException( $i . 'й Аспект не введен.' );
            }
        }
    }
}