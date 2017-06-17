<?php

namespace Middleware;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Symfony\Component\Validator\Constraints\Valid;

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
	 * @param Request $request PSR7 request
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
		if ($request->getMethod() === 'GET') {
			$data = $request->getQueryParams();
			$validator = new Validator();
			$validator->validate($data, $schema->input,
				Constraint::CHECK_MODE_COERCE_TYPES | Constraint::CHECK_MODE_TYPE_CAST);
			if (!$validator->isValid()) {
				return $this->failValidation($response, $validator, 'query validation failed');
			}
			$request = $request->withQueryParams($data);
		} else {
			$content_type = $request->getMediaType();
			if ($content_type !== 'application/json') {
				return get_renderer()->renderAsError($response, 400, 'Invalid request', 'malformed content-type');
			}
			$data = $request->getParsedBody();
			if ($data === null) {
				return get_renderer()->renderAsError($response, 400, 'Invalid request', 'malformed json');
			}

			$validator = new Validator();
			$validator->validate($data, $schema->input, Constraint::CHECK_MODE_TYPE_CAST);
			if (!$validator->isValid()) {
				return $this->failValidation($response, $validator, 'input validation failed');
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

	/**
	 * @param ResponseInterface $response
	 * @param Validator $validator
	 * @param string $reason
	 * @return ResponseInterface
	 */
	protected function failValidation($response, $validator, $reason): ResponseInterface
	{
		$extra = [];
		foreach ($validator->getErrors() as $error) {
			$extra[] = sprintf('[%s] %s', $error['property'], $error['message']);
		}
		return get_renderer()->renderAsError($response, 400, 'Invalid request', $reason,
			$extra);
	}
}
