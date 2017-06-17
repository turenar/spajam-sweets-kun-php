<?php

namespace Middleware;

use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\Request;

/**
 * リクエストおよびレスポンスをjson-schemaを使用してvalidateする。
 *
 * tools/GenerateApiSchema.phpを使用してyamlファイルからjson-schemaを生成する。
 * @package middleware
 */
class ValidationMiddleware
{
	/**
	 * Request Validation
	 *
	 * @param ServerRequestInterface $request PSR7 request
	 * @param ResponseInterface $response PSR7 response
	 * @param callable $next Next middleware
	 *
	 * @return ResponseInterface
	 */
	public function __invoke($request, $response, $next)
	{
		$base_path = explode('?', $request->getUri()->getPath())[0];

		$route = $request->getAttribute('route');
		if ($route) {
			$base_path = $route->getArgument('validator.basePath', $base_path);
		}
		$schema_file = APP_ROOT_PATH . '/generated-api-schema/' . $base_path . '.json';
		$response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');

		if (!file_exists($schema_file)) {
			// エラー出力
			return get_renderer()->renderAsError($response, 404, 'Invalid API endpoint', 'no spec', $schema_file);
		}

		$schema = json_decode(file_get_contents($schema_file));
		if ($request->getMethod() !== 'GET') {
			$content_type = $request->getMediaType();
			if ($content_type !== 'application/json') {
				return get_renderer()->renderAsError($response, 400, 'Invalid request', 'malformed content-type');
			}
			// objectじゃないとvalidatorが通らないの悲しい
			$data = json_decode($request->getBody());

			if ($data === null) {
				return get_renderer()->renderAsError($response, 400, 'Invalid request', 'malformed json');
			}

			$validator = new Validator();
			$validator->check($data, $schema->input);
			if (!$validator->isValid()) {
				$extra = [];
				foreach ($validator->getErrors() as $error) {
					$extra[] = sprintf('[%s] %s', $error['property'], $error['message']);
				}
				return get_renderer()->renderAsError($response, 400, 'Invalid request', 'input validation failed', $extra);
			}
		}

		/** @var ResponseInterface $real_response */
		$real_response = $next($request, $response);
		if ($real_response->getStatusCode() >= 300) {
			return $real_response;
		}
		$real_body = $real_response->getBody();
		$real_body->rewind();
		$validator = new Validator();
		$validator->check(json_decode($real_body->getContents()), $schema->output);
		if ($validator->isValid()) {
			return $real_response;
		} else {
			$extra = [];
			foreach ($validator->getErrors() as $error) {
				$extra[] = sprintf('[%s] %s', $error['property'], $error['message']);
			}
			return get_renderer()->renderAsError($response, 500, 'Invalid request', 'output validation failed', $extra);
		}
	}
}
