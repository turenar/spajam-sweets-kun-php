<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->post('/login', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getParsedBody();
	$id = $data['user_id'];
	$password = $data['password'];

	// FIXME
	if ($id === 'user_id' && $password === 'password') {
		return get_renderer()->render($response, [
			'authentication' => [
				'token' => 'ABCD1234'
			]
		]);
	} else {
		return get_renderer()->renderAsError($response, 403, 'Authentication Failure', 'IDかパスワードが違います。');
	}
});
