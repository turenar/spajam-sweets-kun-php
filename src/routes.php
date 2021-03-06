<?php
// Routes

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/routes/'));
foreach ($rii as $file) {
	if ($file->isDir()) {
		continue;
	}
	if (ends_with($file->getPathname(), '.php')) {
		require_once $file->getPathname();
	}
};
