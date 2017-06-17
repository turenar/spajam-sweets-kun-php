<?php
// DIC configuration

function get_logger_uid_processor()
{
	static $uid_processor = null;
	if ($uid_processor === null) {
		$uid_processor = new \Monolog\Processor\UidProcessor();
	}
	return $uid_processor;
}

function get_logger_from_container($container)
{
	static $logger = null;
	if ($logger === null) {
		$settings = $container->get('settings')['logger'];
		$logger = new Monolog\Logger($settings['name']);
		$logger->pushProcessor(get_logger_uid_processor());
		$logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
	}
	return $logger;
}

/**
 * @param \Monolog\Logger $logger
 * @param Exception $e
 */
function show_exception($logger, $e)
{
	$logger->error(sprintf('%s thrown: %s', get_class($e), $e->getMessage()));
	foreach ($e->getTrace() as $trace) {
		$logger->error(sprintf("\t%s in %s:%s", $trace['function'], $trace['file'] ?? '<unknown>', $trace['line'] ?? -1));
	}
	if ($e->getPrevious()) {
		$logger->error("Caused by:");
		show_exception($logger, $e->getPrevious());
	}
}

$container = $app->getContainer();

// monolog
$container['logger'] = function ($c) {
	return get_logger_from_container($c);
};

$container['errorHandler'] = function ($c) {
	return function ($request, $response, $exception) use ($c) {
		/** @var \Monolog\Logger $logger */
		$logger = get_logger_from_container($c);
		$logger->error('ErrorHandler');
		show_exception($logger, $exception);
		return get_renderer()->renderAsError($response, 500, 'Server Error', 'error occurred',
			['error_id' => get_logger_uid_processor()->getUid()]);
	};
};

$container['phpErrorHandler'] = function ($c) {
	return function ($request, $response, $exception) use ($c) {
		/** @var \Monolog\Logger $logger */
		$logger = get_logger_from_container($c);
		$logger->error('PHPErrorHandler');
		show_exception($logger, $exception);
		return get_renderer()->renderAsError($response, 500, 'Server Error', 'error occurred',
			['error_id' => get_logger_uid_processor()->getUid()]);
	};
};

$container['notFoundHandler'] = function ($c) {
	return function ($request, $response) use ($c) {
		/** @var \Psr\Http\Message\ResponseInterface $res */
		$res = $c['response'];
		return get_renderer()->renderAsError($res, 404, 'Not found', 'API Not found');
	};
};

$container['notAllowedHandler'] = function ($c) {
	return function ($request, $response, $methods) use ($c) {
		/** @var \Psr\Http\Message\ResponseInterface $res */
		$res = $c['response'];
		$allowed = implode(', ', $methods);
		$res = $res->withHeader('Allow', $allowed);
		return get_renderer()->renderAsError($res, 405, 'Method not allowed', sprintf('allowed method: [%s]', $allowed));
	};
};
// propel
require_once __DIR__ . '/../db/generated-conf/config.php';
