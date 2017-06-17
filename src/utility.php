<?php

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\ObjectCollection;

const QUAD_KEY_LEVEL = 18;

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
