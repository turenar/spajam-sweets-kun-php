<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->get('/user/{id:\d+}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$id = $args['id'];

	$user = \ORM\UserQuery::create()
		->filterByUserId($id)
		->findOne();
	if ($user === null) {
		get_renderer()->renderAsError($response, 404, 'Not Found', '指定したユーザーが見つかりません。');
	}
	return get_renderer()->render($response, ['user' => render_as_json($user)]);
})->setArgument('validator.basePath', 'user');
