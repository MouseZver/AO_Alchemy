<?php

declare ( strict_types = 1 );

namespace App\Neuronet;

use Nouvu\Framework\Component\Database\StatementInterface;
use App\Service\Database\ImageSimilarity AS ISimDataService;
use Psr\Container\ContainerInterface;

use function App\Foundation\Helpers\{ app };

class ImageSimilarity
{
	private const SIMILAR_PERCENT = 90;
	
	private array $result = [], $similar = [];
	
	private int $width, $height;
	
	private string $unknown = '{?}';
	
	private ?string $directory = null;
	
	private bool $createDirectory = false, $setTables = false;
	
	private ContainerInterface $container;
	
	public function __construct ()
	{}
	
	public function getName(): string
	{
		return \NeuralNetworkImages :: class;
	}
	
	public function setContainer( ContainerInterface $container ): void
	{
		$this -> container = $container;
	}
	
	public function boot(): void
	{
		$this -> setDirectoryData( $this -> container -> get( 'kernel' ) -> getDataDir() );
		
		$this -> createDirectoryData();
	}
	
	public function setTables(): void
	{
		if ( ! $this -> setTables )
		{
			ISimDataService :: createTables();
			
			$this -> setTables = true;
		}
	}
	
	public function setDirectoryData( string $directory ): void
	{
		$r = new \ReflectionObject( $this );
		
		$this -> directory = $directory . sprintf ( '/%s/data', $r -> getShortName() );
	}
	
	public function setDirectoryApp( string $app ): void
	{
		$this -> directory .= DIRECTORY_SEPARATOR . $app;
	}
	
	public function getDirectoryData(): ?string
	{
		return $this -> directory;
	}
	
	public function createDirectoryData(): void
	{
		if ( ! $this -> createDirectory && ! file_exists ( $this -> getDirectoryData() ) )
		{
			mkdir ( $this -> getDirectoryData(), 0777, true );
			
			$this -> createDirectory = true;
		}
	}
	
	/* 
		$im = imagecreatefrompng( 'ingredients.png' );
		$separate = array_map ( ( fn ( int $a ): array => [ $a, 0 ] ), [0,54,106,160,214] );
		$width = 32;
		$height = 33;
		$iterator = 'interatorToBlackWhitePixels';
		$movement = $ImSim -> movementByX();
		
		$ImSim -> build( $im, $separate, $width, $height, $iterator, $movement );
	 */
	public function build( \GDImage $im, array $separate, int $width, int $height, \Closure $iterator, \Closure $movement = null, \Closure | array $filter = [], int $brightness = 128, bool $createData = true ): void
	{
		$this -> setTables();
		
		$this -> createDirectoryData();
		
		$this -> setSize( $width, $height );
		
		foreach ( $this -> createParticlesData( $im, $separate, $iterator, $movement, $filter, $brightness ) AS [ 'image' => $image, 'points' => $points ] )
		{
			$hash = $this -> hashPoints( $points );
			
			if ( ! $this -> identification( $hash ) )
			{
				$data = $this -> learn( $image, $hash, $points );
				
				$this -> setData( $image, $hash, $points, $data, $createData );
			}
			
			$this -> storage( $image, $hash );
		}
	}
	
	public function movementByX(): \Closure
	{
		return static function ( array &$result, int $x, int $y ): void
		{
			$result['x'] -= $x;
		};
	}
	
	public function movementByY(): \Closure
	{
		return static function ( array &$result, int $x, int $y ): void
		{
			$result['y'] -= $y;
		};
	}
	
	public function filterFillArea( int $x, int $y, int $width, int $height, int $color ): \Closure
	{
		return static function ( array &$result ) use ( $x, $y, $width, $height, $color ): void
		{
			$areaX = $x <= $result['x'] && $result['x'] <= ( $x + $width );
			
			$areaY = $y <= $result['y'] && $result['y'] <= ( $y + $height );
			
			if ( $areaX && $areaY )
			{
				$result['color'] = $color;
			}
		};
	}
	
	public function iteratorСonvertToBlackAndWhite(): \Closure
	{
		return static function ( \GdImage $image, int $x, int $y, int $width, int $height, int $brightness = 128 ): Iterable
		{
			$height += $y;
			$width += $x;
			
			for ( $_y = $y; $_y < $height; $_y++ )
			{
				for ( $_x = $x; $_x < $width; $_x++ )
				{
					$index = imagecolorat ( $image, $_x, $_y );
					
					$r = ( $index >> 16 ) & 0xFF;
					$g = ( $index >> 8 ) & 0xFF;
					$b = $index & 0xFF;
					
					yield [
						'x' => $_x,
						'y' => $_y,
						'color' => ( ( $r + $g + $b ) / 3 > $brightness ? 16777215 : 0 )
					];
				}
			}
		};
	}
	
	public function createParticlesData( \GDImage $im, array $separate, \Closure $iterator, \Closure $movement = null, \Closure | array $filter = [], int $brightness = 128 ): Iterable
	{
		foreach ( $separate AS [ $x, $y ] )
		{
			$image = imagecreatetruecolor ( ...$this -> getSize() );
			
			$points = '';
			
			foreach ( $iterator( $im, $x, $y, ...$this -> getSize(), brightness: $brightness ) AS $result )
			{
				if ( $movement instanceof \Closure )
				{
					$movement( $result, $x, $y );
				}
				
				if ( ! is_array ( $filter ) )
				{
					$filter = [ $filter ];
				}
				
				foreach ( $filter AS $f )
				{
					$f( $result );
				}
				
				$points .= $result['color'] ? 1 : 0;
				
				imagesetpixel ( $image, ...$result );
			}
			
			yield compact ( 'image', 'points' );
		}
	}
	
	public function identification( string $hash ): bool
	{
		return ! empty ( ISimDataService :: findIdByHash( $hash ) );
	}
	
	public function hashPoints( string $points ): string
	{
		return md5 ( $points );
	}
	
	public function learn( \GDImage $image, string $hash, string $points, int | float $similar_percent = self :: SIMILAR_PERCENT ): ?array
	{
		foreach ( $this -> dataIteratorByCategory( $this -> getCategory() ) AS [ 'series' => $series, 'data' => $data ] )
		{
			similar_text ( $data, $points, $percent );
			
			if ( $percent >= $similar_percent )
			{
				$this -> similar[ ( string ) $percent ] = $series;
			}
		}
		
		if ( empty ( $this -> similar ) )
		{
			return null;
		}
		
		ksort ( $this -> similar );
		
		$series = end ( $this -> similar );
		
		$percent = array_key_last ( $this -> similar );
		
		$this -> similar = [];
		
		return compact ( 'percent', 'series' );
	}
	
	public function setData( \GDImage $image, string $hash, string $points, ?array $data, bool $createData = true ): void
	{
		if ( isset ( $data ) )
		{
			$this -> addData( $image, $data, $hash, $points );
		}
		else if ( $createData )
		{
			$this -> createData( $image, $hash, $points );
		}
	}
	
	public function dataIteratorByCategory( string $category ): Iterable
	{
		$stmt = ISimDataService :: findDataByCategory( $category );
		
		while ( $values = $stmt -> get( StatementInterface :: FETCH_ASSOC ) )
		{
			yield $values;
		}
	}
	
	public function addData( \GDImage $image, array $data, string $hash, string $points ): void
	{
		ISimDataService :: insertImageData( 
			$data,
			$hash,
			$this -> getCategory(),
			$points
		);
		
		imagepng ( $image, $this -> getDirectoryData() . sprintf ( '/%s/%s/%s.png', $this -> getCategory(), $data['series'], $hash ) );
	}
	
	public function createData( \GDImage $image, string $hash, string $points ): void
	{
		$percent = 0;
		
		$series = $this -> createSeries();
		
		ISimDataService :: insertNamesData( $series, $this -> unknown );
		
		mkdir ( $this -> getDirectoryData() . sprintf ( '/%s/%s', $this -> getCategory(), $series ), 0777, true );
		
		$this -> addData( $image, compact ( 'series', 'percent' ), $hash, $points );
	}
	
	public function createSeries(): string
	{
		return ( string ) microtime ( true ) . md5 ( ( string ) mt_rand ( 1000, 9999 ) );
	}
	
	public function setSize( int $width, int $height ): void
	{
		$this -> width = $width;
		$this -> height = $height;
	}
	
	public function getSize(): array
	{
		return [ $this -> width, $this -> height ];
	}
	
	public function getCategory(): string
	{
		return md5 ( $this -> width . 'x' . $this -> height );
	}
	
	public function storage( \GDImage $image, string $hash ): void
	{
		imagedestroy ( $image );
		
		$this -> result[] = ISimDataService :: findNameByHashAndCategory( $hash, $this -> getCategory() );
	}
	
	public function getResults(): array
	{
		return $this -> result;
	}
	
	public function getResult( int $position = 0 ): ?string
	{
		return $this -> result[$position] ?? null;
	}
	
	public function clean(): void
	{
		$this -> result = [];
	}
}