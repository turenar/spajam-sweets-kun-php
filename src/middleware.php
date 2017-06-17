<?php
// Application middleware
require_once __DIR__ . '/middleware/RequestValidateMiddleware.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

// e.g: $app->add(new \Slim\Csrf\Guard);
// $app->add(new RequestValidateMiddleware());
