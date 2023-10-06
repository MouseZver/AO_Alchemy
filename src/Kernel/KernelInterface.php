<?php

declare ( strict_types = 1 );

namespace App\Kernel;

use Psr\Container\ContainerInterface;

interface KernelInterface
{
    public function getProjectDir(): string;

    public function getConfigDir(): string;

    public function getDataDir(): string;

    public function getCharset(): string;

    public function getContainer(): ContainerInterface;

    public function getBundlesPath(): string;

    public function registerBundles(): iterable;

    //public function initializeBundles(): void;

    //public function initializeContainer(): void;

    public function boot( mixed ...$classes ): void;

    public function getBundle( string $name ): mixed;

    public function getBundles(): array;
}