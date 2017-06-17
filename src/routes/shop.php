<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->post('/shop/create', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getParsedBody();
	$name = $data['name'];
	$open_time = $data['open_time'] ?? null;
	$close_time = $data['close_time'] ?? null;
	$address = $data['address'];
	$latitude = $data['latitude'];
	$longitude = $data['longitude'];

	$quad_key = new QuadKey();
	$shop = new \ORM\Shop();
	$shop
		->setName($name)
		->setOpenTime($open_time)
		->setCloseTime($close_time)
		->setAddress($address)
		->setLatitude($latitude)
		->setLongitude($longitude)
		->setGeomHash($quad_key->latLngToQuadKey($latitude, $longitude, QUAD_KEY_LEVEL))
		->save();
	return get_renderer()->render($response, ['shop' => render_as_json($shop)]);
});

$app->get('/shop/{id:\d+}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$id = $args['id'];

	$shop = \ORM\ShopQuery::create()
		->filterByShopId($id)
		->findOne();
	if ($shop === null) {
		return get_renderer()->renderAsError($response, 404, 'Not Found', '指定した店が見つかりません。');
	}
	$reviews = \ORM\ReviewQuery::create()
		->filterByShopId($shop->getShopId())
		->find();

	return get_renderer()->render($response, ['shop' => $shop->render($reviews)]);
})->setArgument('validator.basePath', 'shop');

$app->get('/shop/search', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$data = $request->getQueryParams();
	$lat_min = $data['lat_min'];
	$lat_max = $data['lat_max'];
	$long_min = $data['long_min'];
	$long_max = $data['long_max'];

	$quad_key = new QuadKey($lat_min, $long_min);
	$hash_min = $quad_key->latLngToQuadKey($lat_min, $long_min, QUAD_KEY_LEVEL);
	$hash_max = $quad_key->latLngToQuadKey($lat_max, $long_max, QUAD_KEY_LEVEL);

	for ($common_len = 0; $common_len < strlen($hash_max); $common_len++) {
		if (strncmp($hash_min, $hash_max, $common_len) !== 0) {
			break;
		}
	}

//	return get_renderer()->renderAsError($response, 403, 'hoge', 'fuga', [$hash_min, $hash_max]);
	$shops = \ORM\ShopQuery::create()
//		->filterByGeomHash(substr($hash_max, 0, $common_len), \ORM\ShopQuery::GREATER_EQUAL)
//		->filterByGeomHash(substr($hash_min, 0, $common_len), \ORM\ShopQuery::LESS_EQUAL)
		->filterByLatitude(['min' => $lat_min, 'max' => $lat_max])
		->filterByLongitude(['min' => $long_min, 'max' => $long_max])
		->find();

	return get_renderer()->render($response, ['shop' => render_as_json($shops)]);
});
