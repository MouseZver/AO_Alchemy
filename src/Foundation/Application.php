<?php

declare ( strict_types = 1 );

namespace App\Foundation;

use App\Kernel\Kernel;
use App\Kernel\KernelInterface;
use Nouvu\Framework\Foundation\Application AS BaseApplication;

use function App\Foundation\Helpers\{ app, config };

class Application extends BaseApplication
{
	public function __construct ( 
		protected readonly KernelInterface $kernel = new Kernel 
	)
	{
		$kernel -> boot( $this );
	}
    
    public function getName(): string
    {
        return \App :: class;
    }
	
	protected function getHelpersDir(): string
    {
        return $this -> kernel -> getProjectDir() . '/src/Foundation/Helpers';
    }
	
	protected function getPackagesDir(): string
    {
        return $this -> kernel -> getConfigDir() . '/packages';
    }
	
	protected function initializeHelpers(): void
	{
		foreach ( glob ( $this -> getHelpersDir() . '/*.php' ) AS $file )
		{
			require $file;
		}

		if ( ! function_exists ( \App\Foundation\Helpers\app :: class ) )
		{
			throw new \LogicException( sprintf ( 'Parent Helper function "%s" not found', \App\Foundation\Helpers\app :: class ) );
		}
		
		app( $this );
	}
	
	protected function initializePackages(): void
	{
		foreach ( glob ( $this -> getPackagesDir() . '/*.php' ) AS $file )
		{
			$package = require $file;
			
			foreach ( $package AS $name => $closure )
			{
				$this -> container -> set( $name, $closure );
			}
		}
	}
	
	protected function configureBuilder(): void
	{
		foreach ( glob ( $this -> kernel -> getConfigDir() . '/*.php' ) AS $file )
		{
			$name = basename ( $file, '.php' );
			
			$config = require $file;
			
			$this -> container -> get( 'Repository' ) -> add( $name, $config );
		}
	}

	protected function registerConfiguration(): void
	{
		ini_set ( 'date.timezone', config( 'app.timezone', '' ) );
		
		$ini_set_data = [
			'error_reporting' => config( 'app.debug.error' ) ? E_ALL : 0,
			'html_errors' => ( int ) config( 'app.debug.html' ),
			'log_errors' => ( int ) config( 'app.debug.error' ),
			'log_errors_max_len' => config( 'app.debug.log_errors_max_len' ),
			'ignore_repeated_errors' => ( int ) config( 'app.debug.ignore_repeated_errors' ),
			'ignore_repeated_source' => ( int ) config( 'app.debug.ignore_repeated_source' ),
			'error_log' => $this -> kernel -> getProjectDir() . sprintf ( config( 'app.debug.error_log' ), date ( 'Y-m-d' ) ),
			'display_errors' => config( 'app.debug.display' ) ? 'on' : 'off',
			'display_startup_errors' => ( int ) config( 'app.debug.display' ),
			//'default_charset' => config( 'app.default_charset' ),
			'default_charset' => $this -> kernel -> getCharset(),
		];

		foreach ( $ini_set_data AS $option => $value )
		{
			ini_set ( $option, ( string ) $value );
		}
	}
}
