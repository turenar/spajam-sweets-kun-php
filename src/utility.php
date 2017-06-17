<?php

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\ObjectCollection;

const QUAD_KEY_LEVEL = 18;
const SPAWN_RANDOMIZE_RANGE = 300;
const RANDOM_ROUND = 3;
const TRANSACTION_PATIENCE = 10;

function transaction($callable)
{
	return \Propel\Runtime\Propel::getConnection()->transaction($callable);
}

function get_renderer()
{
	static $renderer;
	if (!$renderer) {
		$renderer = new JsonRenderer();
	}
	return $renderer;
}

/**
 * @param ObjectCollection|ActiveRecordInterface $data
 * @return array
 */
function render_as_json($data)
{
	if ($data === null) {
		return null;
	} else if ($data instanceof ObjectCollection) {
		$ret = [];
		foreach ($data as $datum) {
			$ret[] = $datum->render();
		}
		return $ret;
	} else {
		return $data->render();
	}
}

function start_with($haystack, $needle)
{
	return strpos($haystack, $needle) === 0;
}

function ends_with($haystack, $needle)
{
	return !!preg_match('@' . preg_quote($needle, '@') . '$@', $haystack);
}

function randomize_lat_long(QuadKey $quad_key, $lat, $long, $max_radius)
{
	$meter_per_degree = $quad_key->getMeterPerLatLngDegree($lat, $long);
	$radius = 0;
	$theta = 0;
	for ($i = 0; $i < RANDOM_ROUND; $i++) {
		$radius += mt_rand() / mt_getrandmax() * $max_radius / RANDOM_ROUND;
		$theta += mt_rand() / mt_getrandmax() * 360 / RANDOM_ROUND;
	}
	$move_circle = [$radius * cos($theta * 2 * M_PI / 360), $radius * sin($theta * 2 * M_PI / 360)];
	$randomization = [$move_circle[0] / $meter_per_degree[0], $move_circle[1] / $meter_per_degree[1]];
	/*\Monolog\Registry::getInstance('__default__')->debug('randomize!', [
		'max_radius' => $max_radius,
		'radius' => $radius,
		'theta' => $theta,
		'moving' => $move_circle,
		'randomization' => $randomization
	]);*/
	return [$lat + $randomization[0], $long + $randomization[1]];
}
