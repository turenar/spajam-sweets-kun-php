<?php

require_once __DIR__ . '/bootstrap.php';

if ($argc != 3) {
	fprintf(STDERR, "specify lat long\n");
	exit(1);
}
$lat = $argv[1];
$long = $argv[2];

$quad_key = new QuadKey();
echo $quad_key->latLngToQuadKey($lat, $long, QUAD_KEY_LEVEL);
