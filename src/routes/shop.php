<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

	$quad_key = new QuadKey();
	$hash_min = $quad_key->latLngToQuadKey($lat_min, $long_min, QUAD_KEY_LEVEL);
	$hash_max = $quad_key->latLngToQuadKey($lat_max, $long_max, QUAD_KEY_LEVEL);
//	return get_renderer()->renderAsError($response, 403, 'hoge', 'fuga', [$hash_min, $hash_max]);
	$shops = \ORM\ShopQuery::create()
		->filterByGeomHash(['min' => $hash_min, 'max' => $hash_max])
		->filterByLatitude(['min' => $lat_min, 'max' => $lat_max])
		->filterByLongitude(['min' => $long_min, 'max' => $long_max])
		->find();

	return get_renderer()->render($response, ['shop' => render_as_json($shops)]);
});
