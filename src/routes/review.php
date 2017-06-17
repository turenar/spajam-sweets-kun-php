<?php

use Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->post('/review/create', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getParsedBody();
	/** @var \ORM\User $user */
	$user = $request->getAttribute('user');

	$shop_id = $data['shop_id'];
	$rating = $data['rating'];
	$review_text = $data['review_text'];
	$sweet_type = $data['sweet_type'];

	$shop = \ORM\ShopQuery::create()
		->filterByShopId($shop_id)
		->findOne();
	if ($shop === null) {
		return get_renderer()->renderAsError($response, 400, 'Invalid shop id', '指定した店が見つかりません');
	}

	// TODO
	$lat = $shop->getLatitude();
	$long = $shop->getLongitude();
	$quadKey = new QuadKey();
	$hash = $quadKey->latLngToQuadKey($lat, $long, 18);
	$review = new \ORM\Review();
	$review
		->setShopId($shop->getShopId())
		->setUserId($user->getUserId())
		->setRating($rating)
		->setReviewText($review_text)
		->setSweetType($sweet_type)
		->setLatitude($lat)
		->setLongitude($long)
		->setGeomHash($hash)
		->save();
	return get_renderer()->render($response, ['review' => $review->render()]);
})->add(new AuthMiddleware());
