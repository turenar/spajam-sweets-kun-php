<?php

require_once __DIR__ . '/bootstrap.php';

$likes = \ORM\LikesQuery::create()->find();
$likes_max = count($likes) - 1;
$reviews = \ORM\ReviewQuery::create()->find()->toKeyIndex('ReviewId');
$reviews_max = count($reviews) - 1;
/** @var \ORM\Authentication[] $auths */
$auths = \ORM\AuthenticationQuery::create()->find()->toKeyIndex('UserId');
$auths_max = count($auths) - 1;

/**
 * @param $id
 * @param \ORM\Authentication $auth
 */
function like($id, $auth)
{
	$curl = curl_init('http://localhost:8080/review/' . $id . '/likes');
	$options = [];
	$options[CURLOPT_POST] = true;
	$options[CURLOPT_POSTFIELDS] = json_encode([]);
	$options[CURLOPT_HTTPHEADER] = [
		'Authorization: bearer ' . $auth->getToken(),
		'Content-Type: application/json',
	];
	curl_setopt_array($curl, $options);
	curl_exec($curl);
	curl_close($curl);
}

for ($i = 0; $i < 40; $i++) {
	$id = mt_rand(1, 2);
	like($id, $auths[mt_rand(1, $auths_max)]);
}
for ($i = 0; $i < 100; $i++) {
	$id = mt_rand(1, 10);
	like($id, $auths[mt_rand(1, $auths_max)]);
}
for ($i = 0; $i < 200; $i++) {
	$id = mt_rand(1, 40);
	like($id, $auths[mt_rand(1, $auths_max)]);
}
for ($i = 0; $i < 200; $i++) {
	$id = mt_rand(1, 200);
	like($id, $auths[mt_rand(1, $auths_max)]);
}
