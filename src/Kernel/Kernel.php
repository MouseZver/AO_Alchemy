<?php

declare ( strict_types = 1 );

namespace App\Kernel;

use Psr\Container\ContainerInterface;

class Kernel implements KernelInterface
{
	private string $projectDir;
	
	private array $bundles;

	private ContainerInterface $container;
	
	public function getProjectDir(): string
    {
        if ( ! isset ( $this -> projectDir ) )
		{
            $r = new \ReflectionObject( $this );

            if ( ! is_file ( $dir = $r -> getFileName() ) )
			{
                throw new \LogicException( sprintf ( 'Cannot auto-detect project dir for kernel of class "%s".', $r -> name ) );
            }
			
			do
			{
				if ( $dir === dirname ( $dir ) )
				{
					return $this -> projectDir = $dir;
				}
				
				$dir = dirname ( $dir );
			}
			while ( ! is_file ( $dir . '/composer.lock' ) );
			
			$this -> projectDir = $dir;
        }
		
        return $this -> projectDir;
    }

	public function getConfigDir(): string
	{
		return $this -> getProjectDir() . '/config';
	}
	
	public function getDataDir(): string
	{
	    return $this -> getProjectDir() . '/data';
	}

	public function getCharset(): string
    {
        return $this -> container ?-> get( 'Repository' ) -> get( 'app.charset' ) ?? 'UTF-8';
    }
	
	protected function getContainerBaseClass(): string
    {
        return \Container :: class;
    }
	
	public function getContainer(): ContainerInterface
	{
		if ( is_null ( $this -> container ) )
		{
            throw new \LogicException( 'Cannot retrieve the container from a non-booted kernel.' );
        }
		
		return $this -> container;
	}
	
	public function getBundlesPath(): string
    {
        return $this -> getConfigDir() . '/bundles.php';
    }
	
	public function registerBundles(): iterable
    {
        $contents = require $this -> getBundlesPath();

        foreach ( $contents AS $class )
		{
            yield new $class;
        }
    }

	protected function initializeBundles(): void
    {
        $this -> bundles = [];
        
        foreach ( $this -> registerBundles() AS $bundle )
        {
            if ( method_exists ( $bundle, 'getName' ) )
            {
                $name = $bundle -> getName();
            }
            else
            {
                $name = ( new \ReflectionObject( $bundle ) ) -> getShortName();
            }
            
            
            if ( isset ( $this -> bundles[$name] ) )
			{
                throw new \LogicException( sprintf ( 'Trying to register two bundles with the same name "%s".', $name ) );
            }
			
            $this -> bundles[$name] = $bundle;
        }
    }

	protected function initializeContainer(): ContainerInterface
	{
		$container = $this -> getBundle( $this -> getContainerBaseClass() );
		
		foreach ( $this -> getBundles() AS $name => $bundle )
		{
			$container -> set( $name, fn() => $bundle );
		}
		
		$container -> set( 'kernel', fn() => $this );
		
		return $container;
	}
	
	private function preBoot(): ContainerInterface
    {
        $this -> initializeBundles();

        return $this -> initializeContainer();
    }
	
	public function boot( mixed ...$classes ): void
    {
        $this -> container ??= $this -> preBoot();

        foreach ( [ ...$this -> getBundles(), ...$classes ] AS $bundle )
		{
            if ( method_exists ( $bundle, 'setContainer' ) )
			{
				$bundle -> setContainer( $this -> container );
			}
			
			if ( method_exists ( $bundle, 'boot' ) )
			{
				$bundle -> boot();
			}
        }
    }

	public function getBundle( string $name ): mixed
    {
        if ( ! isset ( $this -> bundles[$name] ) )
		{
            throw new \InvalidArgumentException ( sprintf ( 'Bundle "%s" does not exist or it is not enabled. Maybe you forgot to add it in the "registerBundles()" method of your "%s.php" file?', $name, get_debug_type ( $this ) ) );
        }

        return $this -> bundles[$name];
    }
	
	public function getBundles(): array
    {
        return $this -> bundles;
    }
}