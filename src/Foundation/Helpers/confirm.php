<?php

declare ( strict_types = 1 );

namespace App\Foundation\Helpers;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function App\Foundation\Helpers\{ app };

function confirm( SymfonyStyle $io, string $question, bool $default = true, string $trueAnswerRegex = '/^(y|1)/i' ): bool
{
	return $io -> askQuestion( new ConfirmationQuestion( $question, $default, $trueAnswerRegex ) );
}