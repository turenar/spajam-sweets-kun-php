<?php
namespace middleware;

use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;

/**
 * リクエストおよびレスポンスをjson-schemaを使用してvalidateする。
 *
 * tools/GenerateApiSchema.phpを使用してyamlファイルからjson-schemaを生成する。
 * @package middleware
 */
class RequestValidateMiddleware
{
	/**
	 * RequestValidateMiddleware constructor.
	 * @param string|null $json_name
	 *   チェック用のyamlファイルパス (generated-api-schemaから.yamlを除いた相対パス)。
	 *   nullの場合は、リクエストパスを使用する。
	 * @param bool $check_out 出力をチェックするかどうか (デバッグ用、trueを強く推奨)
	 */
	public function __construct($json_name = null, $check_out = true)
	{
		$this->yaml_path = $json_name;
		$this->check_out = $check_out;
	}

	/**
	 * Request Validation
	 *
	 * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
	 * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
	 * @param  callable $next Next middleware
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function __invoke($request, $response, $next)
	{
		$request_target = $this->yaml_path ?? explode('?', $request->getRequestTarget())[0];
		$schema_file = APP_ROOT_PATH . '/generated-api-schema/' . $request_target . '.json';
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
		if (!$this->check_out) {
			return $real_response;
		} else {
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
}
