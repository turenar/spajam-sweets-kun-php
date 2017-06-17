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
$auths_max = count($auths) - 1;

for ($i = 0; $i < 200; $i++) {
	$curl = curl_init('http://localhost:8080/review/create');
	$options = [];
	$options[CURLOPT_POST] = true;
	$args = [
		'shop_id' => mt_rand(1, 6),
		'sweet_type' => mt_rand(1, 5),
	];
	if (mt_rand(0, 2)) {
		$args['rating'] = (int)((mt_rand(1, 5) + mt_rand(1, 5) + mt_rand(1, 5) + mt_rand(1, 5) + mt_rand(1, 5)) / 5);
	}
	if (!mt_rand(0, 6)) {
		$args['review_text'] = $texts[mt_rand(0, $texts_max)];
	}
	$options[CURLOPT_POSTFIELDS] = json_encode($args);
	$options[CURLOPT_HTTPHEADER] = [
		'Authorization: bearer ' . $auths[mt_rand(1, $auths_max)]->getToken(),
		'Content-Type: application/json',
	];
	curl_setopt_array($curl, $options);
	curl_exec($curl);
	curl_close($curl);
}
