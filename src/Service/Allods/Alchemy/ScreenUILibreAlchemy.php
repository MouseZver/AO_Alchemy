<?php

declare ( strict_types = 1 );

namespace App\Service\Allods\Alchemy;

use function App\Foundation\Helpers\{ app, config };

class ScreenUILibreAlchemy
{
	private string $imagesDir;
	
	private static function createFileName(): string
	{
		return app() -> kernel -> getProjectDir() . sprintf ( '/var/images/%s.png', microtime ( true ) );
	}
	
	private static function getGDImage( array $area ): \GdImage
	{
		$screenPath = self :: createFileName();
		
		app() -> nircmd -> savescreenshot( $screenPath, ...$area );
		
		return imagecreatefrompng ( $screenPath );
	}
	
	public static function OnAlchemyReactionFinished(): \GdImage
	{
		return self :: getGDImage( config( 'allods.LibreAlchemy.screenArea.OnAlchemyReactionFinished' ) );
	}
	
	public static function ingredients(): \GdImage
	{
		return self :: getGDImage( config( 'allods.LibreAlchemy.screenArea.ingredients' ) );
	}
	
	public static function UIInterface(): \GdImage
	{
		return self :: getGDImage( config( 'allods.LibreAlchemy.screenArea.checkStart' ) );
	}
}