<?php

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\ObjectCollection;

require_once __DIR__ . '/middleware/JsonRenderer.php';

function transaction($callable)
{
	return \Propel\Runtime\Propel::getConnection()->transaction($callable);
}

$__renderer = new \middleware\JsonRenderer();
function get_renderer()
{
	global $__renderer;
	return $__renderer;
}

/**
 * @param ObjectCollection|ActiveRecordInterface $data
 * @return array
 */
function format_as_response($data)
{
	if ($data instanceof ObjectCollection) {
		$ret = [];
		foreach ($data as $datum) {
			$ret[] = $datum->format_as_response();
		}
		return $ret;
	} else {
		return $data->format_as_response();
	}
}
