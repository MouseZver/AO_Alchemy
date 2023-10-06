<?php

declare ( strict_types = 1 );

namespace App\Service\Allods;

use function App\Foundation\Helpers\{ container };

class InterfaceRecognition
{
    public static function UIInterface( \GDImage $im, bool $createData ): void
    {
        $n = container( \NeuralNetworkImages :: class );
        
        $n -> build(
            im: $im,
            separate: [ [ 0, 0 ] ],
            width: imagesx ( $im ),
            height: imagesy ( $im ),
            iterator: $n -> iteratorСonvertToBlackAndWhite(),
            createData: $createData
        );
    }
	
    public static function ingredients( \GDImage $im, bool $createData ): void
    {
		$n = container( \NeuralNetworkImages :: class );
        
        $n -> build(
            im: $im,
            separate: array_map ( ( fn ( int $a ): array => [ $a, 0 ] ), [0,54,106,160,214] ),
            width: 32,
            height: 33,
            iterator: $n -> iteratorСonvertToBlackAndWhite(),
            movement: $n -> movementByX(),
            createData: $createData
        );
	}
	
	public static function OnAlchemyReactionFinished( \GDImage $im, bool $createData ): void
    {
		$n = container( \NeuralNetworkImages :: class );
        
        $n -> build(
            im: $im,
            separate: array_map ( ( fn ( int $a ): array => [ 0, $a ] ), [0,42,85] ),
            width: 38,
            height: 38,
            iterator: $n -> iteratorСonvertToBlackAndWhite(),
            movement: $n -> movementByY(),
            createData: $createData
        );
	}
}