<?php

require_once __DIR__ . '/bootstrap.php';

$orig_sql = file_get_contents('php://stdin');
$con = \Propel\Runtime\Propel::getConnection(\ORM\Map\UserTableMap::DATABASE_NAME);
$sqls = explode(';', $orig_sql);
foreach ($sqls as $sql) {
	$sql = trim($sql);
	if ($sql) {
		$con->exec($sql);
	}
}
