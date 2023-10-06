<?php

declare ( strict_types = 1 );

namespace App\Kernel;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class CliKernel extends Kernel
{
	/*
		( new App\Foundation\Application( new App\Kernel\CliKernel ) )
			-> kernel 
			-> console( new Symfony\Component\Console\Application ) 
			-> run();
	*/
	public function console( Application $application ): Application
	{
		// $application -> addCommands( $array );
		
		foreach ( $this -> getBundles() AS $bundle )
		{
			if ( $bundle instanceof Command )
			{
			    $application -> add( $bundle );
			}
		}
		
		return $application;
	}
}