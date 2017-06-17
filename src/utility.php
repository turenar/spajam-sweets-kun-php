<?php

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\ObjectCollection;

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

function start_with($haystack, $needle)
{
	return strpos($haystack, $needle) === 0;
}

function ends_with($haystack, $needle)
{
	return !!preg_match('@' . preg_quote($needle, '@') . '$@', $haystack);
}
