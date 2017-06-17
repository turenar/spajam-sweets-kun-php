<?php

use middleware\AuthMiddleware;
use middleware\RequestValidateMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->group('/user', function () {
	$this->get('', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
		/** @var \ORM\User $user */
		$user = $request->getAttribute('user');

		return get_renderer()->render($response, [
			'user' => $user->format_as_response(),
			'family' => $user->getFamily()->format_as_response()]);
	})->add(new AuthMiddleware())->add(new RequestValidateMiddleware());

	$this->post('/add', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
		$data = transaction(function () {
			$family = new \ORM\Family();
			$family->setToken(sha1(mt_rand() . uniqid(gethostname(), true)))
				->save();

			$user = new \ORM\User();
			$user->setAccessToken(sha1(mt_rand() . uniqid(gethostname(), true)))
				->setFamilyId($family->getFamilyId())
				->save();

			return [
				'user' => $user->format_as_response(),
				'family' => $family->format_as_response()];
		});
		return get_renderer()->render($response, $data);
	})->add(new RequestValidateMiddleware());

	$this->post('/connect', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
		$family = \ORM\FamilyQuery::create()
			->filterByToken($request->getParsedBody()['family']['token'])
			->findOne();
		if ($family === null) {
			return get_renderer()->renderAsError($response, 400, 'Bad request', 'invalid token');
		}

		$user = new \ORM\User();
		$user->setAccessToken(sha1(mt_rand() . uniqid(gethostname(), true)))
			->setFamilyId($family->getFamilyId())
			->save();

		return get_renderer()->render($response, [
			'user' => $user->format_as_response(),
			'family' => $family->format_as_response()]);
	})->add(new RequestValidateMiddleware());
});
