<?php

declare ( strict_types = 1 );

namespace App\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Service\Allods\MenuAlchemy;

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

        $bot = new MenuAlchemy( $this -> io, $input, $output );

        $bot -> run();
		
        return Command :: SUCCESS;
    }
}