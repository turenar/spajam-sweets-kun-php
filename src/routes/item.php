<?php

use middleware\AuthMiddleware;
use middleware\RequestValidateMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->group('/item', function () {
	$this->post('/candidates', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
		$names = $request->getParsedBody()['query']['name'];

		$data = [];
		foreach ($names as $name) {
			$item = \ORM\ItemMasterQuery::create()
				->useItemSearchQuery()
				->filterBySearchWord(preg_replace('|\s|u', '', $name))
				->endUse()
				->findOne();
			if ($item !== null) {
				$datum = $item->format_as_response();
				$datum['original_name'] = $name;
				$data[] = $datum;
			}
		}

		return get_renderer()->render($response, ['item_master' => $data]);
	})->add(new RequestValidateMiddleware());


	$this->get('/list', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
		/** @var \ORM\User $user */
		$user = $request->getAttribute('user');

		$items = \ORM\UserItemQuery::create()
			->filterByFamilyId($user->getFamilyId())
			->find();

		return get_renderer()->render($response, ['user_item' => format_as_response($items)]);
	})->add(new RequestValidateMiddleware())->add(new AuthMiddleware());


	$this->post('/add', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
		/** @var \ORM\User $user */
		$user = $request->getAttribute('user');

		$items = $request->getParsedBody()['user_item'];

		$ret = transaction(function () use ($items, $user) {
			$data = [];
			foreach ($items as $item) {
				$user_item = new \ORM\UserItem();
				$user_item->setFamilyId($user->getFamilyId())
					->setExpireDate($item['expire_date'])
					->setItemId($item['item_id'] ?? null)
					->setItemName($item['item_name'])
					->setPrice($item['price'] ?? null)
					->save();
				$data[] = $user_item->format_as_response();
			}
			return ['user_item' => $data];
		});

		return get_renderer()->render($response, $ret);
	})->add(new RequestValidateMiddleware())->add(new AuthMiddleware());


	$this->post('/delete', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
		/** @var \ORM\User $user */
		$user = $request->getAttribute('user');

		$ids = $request->getParsedBody()['user_item_id'];

		$deleted_rows = transaction(function () use ($ids, $user) {
			return \ORM\UserItemQuery::create()
				->filterByUserItemId($ids, \ORM\UserItemQuery::IN)
				->filterByFamilyId($user->getFamilyId())
				->delete();
		});

		if ($deleted_rows === 0) {
			return get_renderer()->renderAsError($response, 422, 'Unprocessable Entity', 'valid user_item_id is not found');
		} else {
			return get_renderer()->render($response);
		}
	})->add(new RequestValidateMiddleware())->add(new AuthMiddleware());
});
