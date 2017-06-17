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

	$lat = $shop->getLatitude();
	$long = $shop->getLongitude();
	$quad_key = new QuadKey();
	$randomized = randomize_lat_long($quad_key, $lat, $long, SPAWN_RANDOMIZE_RANGE);
	$hash = $quad_key->latLngToQuadKey($randomized[0], $randomized[1], QUAD_KEY_LEVEL);
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

$app->get('/review/search', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getQueryParams();
	$lat_min = $data['lat_min'];
	$lat_max = $data['lat_max'];
	$long_min = $data['long_min'];
	$long_max = $data['long_max'];

	$quad_key = new QuadKey();
	$hash_min = $quad_key->latLngToQuadKey($lat_min, $long_min, QUAD_KEY_LEVEL);
	$hash_max = $quad_key->latLngToQuadKey($lat_max, $long_max, QUAD_KEY_LEVEL);
//	return get_renderer()->renderAsError($response, 403, 'hoge', 'fuga', [$hash_min, $hash_max]);
	$reviews = \ORM\ReviewQuery::create()
		->filterByGeomHash(['min' => $hash_min, 'max' => $hash_max])
		->filterByLatitude(['min' => $lat_min, 'max' => $lat_max])
		->filterByLongitude(['min' => $long_min, 'max' => $long_max])
		->find();

	return get_renderer()->render($response, ['review' => render_as_json($reviews)]);
});
