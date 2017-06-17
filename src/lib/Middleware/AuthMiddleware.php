<?php

namespace Middleware;

use ORM\UserQuery;

class AuthMiddleware
{
	/**
	 * リクエストからx-access-tokenヘッダを取得し、validであることを確認する。
	 * validである場合、$request->getAttribute('user');で該当ユーザーの \ORM\User インスタンスを返す
	 *
	 *
	 * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
	 * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
	 * @param  callable $next Next middleware
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function __invoke($request, $response, $next)
	{
		$authorizations = $request->getHeader('Authorization');
		if (!$authorizations) {
			return $this->failAuth($response);
		}
		$authorization = end($authorizations);
		$matched = preg_match(/** @lang regexp */
			'/^bearer\s+([a-zA-Z0-9]+)\s*$/i', $authorization, $matches);
		if (!$matched) {
			return $this->failAuth($response);
		}
		$token = $matches[1];
		$user = UserQuery::create()
			->useAuthenticationQuery()
			->filterByToken($token)
			->endUse()
			->findOne();
		if ($user === null) {
			return $this->failAuthToken($response);
		}
		$request = $request->withAttribute('user', $user);
		return $next($request, $response);
	}

	protected function failAuth($response)
	{
		return get_renderer()->renderAsError($response, 401, 'Authorization required', 'invalid request')
			->withHeader('WWW-Authenticate', 'Bearer realm="auth_required"');
	}

	protected function failAuthToken($response)
	{
		return get_renderer()->renderAsError($response, 401, 'Authorization required', 'invalid token')
			->withHeader('WWW-Authenticate', 'Bearer realm="auth_required" error="invalid_token"');
	}

}
