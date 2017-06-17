<?php

// レポジトリの親ディレクトリにpassword.incを保管することで、データベース接続に必要なパスワードが変えられる。
if (!defined('PROPEL_INC_INCLUDED_AB58D2CA')) {
	define('PROPEL_INC_INCLUDED_AB58D2CA', true);
	function get_db_password()
	{
		if (file_exists(__DIR__ . '/../../password.inc')) {
			include_once __DIR__ . '/../../password.inc';
			return DB_PASSWORD;
		} else {
			return 'a+cvL~.R/4lkj~44';
		}
	}
}
return [
	'propel' => [
		'database' => [
			'connections' => [
				'fresh_fridge' => [
					'adapter' => 'mysql',
					'classname' => 'Propel\Runtime\Connection\ConnectionWrapper',
					'dsn' => 'mysql:host=localhost;dbname=fresh_fridge;charset=utf8',
					'user' => 'fresh_fridge',
					'password' => get_db_password(),
					'attributes' => []]]],
		'runtime' => [
			'defaultConnection' => 'fresh_fridge',
			'connections' => ['fresh_fridge']],
		'generator' => [
			'defaultConnection' => 'fresh_fridge',
			'connections' => ['fresh_fridge']]]];
