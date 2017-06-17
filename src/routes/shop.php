<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->get('/shop/{id}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	return get_renderer()->render($response, [
		'shop' => [
			'name' => 'おいしいケーキ デ・リ・シャス',
			'address' => '東京都百代田区百代田1-1-1',
			'latitude' => '139.691',
			'longitude' => '35.689',

			'review' => [],
		]
	]);
});
