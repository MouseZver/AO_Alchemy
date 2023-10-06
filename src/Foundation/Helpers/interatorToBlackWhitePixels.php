<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

//use function App\Foundation\Helpers\{ app };

function interatorToBlackWhitePixels( \GdImage $image, int $x, int $y, int $width, int $height, int $brightness = 128 ): Iterable
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
}