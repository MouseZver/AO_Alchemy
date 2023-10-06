<?php

return [
	'charset' => 'UTF-8',

	'timezone' => 'Europe/Moscow',
	
	/*
		- Err conf.
	*/
	'debug' => [
		'error' => true,
		'display' => true,
		'error_log' => '/var/logs/Debug-%s.log',
		'log_errors_max_len' => 0,
		/* 'ignore_repeated_errors' => true,
		'ignore_repeated_source' => false, */
		'html' => false,
	],
];

