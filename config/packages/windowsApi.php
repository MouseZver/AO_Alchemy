<?php

use function App\Foundation\Helpers\{ config };

return [
    Nircmd :: class => fn () => new Nouvu\Windows\Nircmd,
    Arduino\Mouse :: class => fn () => new Nouvu\ArduinoNanoV3\Mouse( config( 'allods.arduino.com' ) ),
];