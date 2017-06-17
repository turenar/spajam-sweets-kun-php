<?php

require_once __DIR__ . '/bootstrap.php';

$texts = [
	'これはレビューです。これはレビューです。これはレビューです。これはレビューです。これはレビューです。',
	'This is a review.',
	'この店は普通です。',
	'この店はまあまあです。',
];
$texts_max = count($texts) - 1;
/** @var \ORM\Authentication[] $auths */
$auths = \ORM\AuthenticationQuery::create()->find()->toKeyIndex('UserId');

$icons = [
	null,
	[0],
	[1],
	[0, 1, 4],
	[3],
	[0, 1, 2, 3, 4],
	[3, 4],
];

for ($i = 0; $i < 200; $i++) {
	$curl = curl_init('http://localhost:8080/review/create');
	$options = [];
	$options[CURLOPT_POST] = true;
	$shop_id = mt_rand(1, 6);
	$icon_candidates = $icons[$shop_id];
	$args = [
		'shop_id' => $shop_id,
		'sweet_type' => $icon_candidates[array_rand($icon_candidates)],
	];
	if (mt_rand(0, 2)) {
		$args['rating'] = (int)((mt_rand(1, 5) + mt_rand(1, 5) + mt_rand(1, 5) + mt_rand(1, 5) + mt_rand(1, 5)) / 5);
	}
	if (!mt_rand(0, 6)) {
		$args['review_text'] = $texts[array_rand($texts)];
	}
	$options[CURLOPT_POSTFIELDS] = json_encode($args);
	$options[CURLOPT_HTTPHEADER] = [
		'Authorization: bearer ' . $auths[array_rand($auths)]->getToken(),
		'Content-Type: application/json',
	];
	curl_setopt_array($curl, $options);
	curl_exec($curl);
	curl_close($curl);
}
