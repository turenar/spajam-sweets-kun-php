<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->post('/login', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getParsedBody();
	$mail = $data['email'];
	$password = $data['password'];

	$authentication = \ORM\AuthenticationQuery::create()
		->filterByEmail($mail)
		->findOne();
	if ($authentication && password_verify($password, $authentication->getPassword())) {
		return get_renderer()->render($response, [
			'authentication' => [
				'user_id' => $authentication->getUserId(),
				'token' => $authentication->getToken()
			]
		]);
	} else {
		return get_renderer()->renderAsError($response, 403, 'Authentication Failure', 'IDかパスワードが違います。');
	}
});

$app->post('/register', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getParsedBody();
	$mail = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
	$password = $data['password'];

	if (!$mail) {
		return get_renderer()->renderAsError($response, 400, 'Malformed request', 'メールアドレスが正しい形式ではありません。');
	}
	$registered = \ORM\AuthenticationQuery::create()
		->filterByEmail($mail)
		->exists();
	if ($registered) {
		return get_renderer()->renderAsError($response, 400, 'Already registered', 'すでに登録されているメールアドレスです。');
	}

	return transaction(function () use ($response, $password, $mail) {
		$user = new \ORM\User();
		$user
			->save();
		$authentication = new \ORM\Authentication();
		$authentication
			->setUserId($user->getUserId())
			->setEmail($mail)
			->setPassword(password_hash($password, PASSWORD_DEFAULT))
			->setToken(hash('sha256', uniqid(microtime(), true)))
			->save();
		return get_renderer()->render($response, ['authentication' => $authentication->render()]);
	});
});
