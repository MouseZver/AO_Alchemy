<?php

declare ( strict_types = 1 );

namespace App\Service\Allods\Alchemy;

use function App\Foundation\Helpers\{ app, config, container };

class DirectInputMouse
{
	public static function leftClick( int $x, int $y ): void
	{
		app() -> nircmd -> setcursor( $x, $y );
		
		container( \Arduino\Mouse :: class ) -> leftClick();
	}
	
	public static function preparePotion(): void
	{
		self :: leftClick( ...config( 'allods.LibreAlchemy.clickArea.prepare' ) );
	}
}