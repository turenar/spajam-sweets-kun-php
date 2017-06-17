<?php
return [
	'settings' => [
		'displayErrorDetails' => false, // set to false in production
		'addContentLengthHeader' => true, // Allow the web server to send the content-length header
		'determineRouteBeforeAppMiddleware' => true,
		// Renderer settings
		'renderer' => [],

		// Monolog settings
		'logger' => [
			'name' => 'slim-app',
			'path' => __DIR__ . '/../logs/app.log',
			'level' => \Monolog\Logger::DEBUG,
		],
	],
];
