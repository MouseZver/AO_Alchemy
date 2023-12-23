<?php

declare ( strict_types = 1 );

namespace App\Console\Command;

use App\Service\Allods\Alchemy\{ MenuAlchemy, SessionAlchemy };
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function App\Foundation\Helpers\{ config, container };

class BotAllodsAlchemy extends Command
{
	protected static $defaultName = 'bot:allods-alchemy';
	
	private SymfonyStyle $io;
	
	public function getName(): string
	{
		return 'Alchemy';
	}

	protected function configure()
	{
		$this
			-> setDescription( 'Бот для варки зелий в игре Аллоды Онлайн' )
			-> setHelp( 'This command does something' );
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$this -> io = new SymfonyStyle( $input, $output );
		
		config() -> set( 'database.file', $this -> getName() . '.db' );
		
		container( \NeuralNetworkImages :: class ) -> setDirectoryApp( app: $this -> getName() );
		
		$session = new SessionAlchemy( name: $this -> getName() );

		$bot = new MenuAlchemy( io: $this -> io, input: $input, output: $output, session: $session );

		$bot -> run();
		
		return Command :: SUCCESS;
	}
}