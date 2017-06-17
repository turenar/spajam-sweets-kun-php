<?php
namespace middleware;

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
		$access_token = $request->getHeader('x-access-token');
		$user = UserQuery::create()
			->filterByAccessToken($access_token)
			->findOne();
		if ($user === null) {
			return get_renderer()->renderAsError($response, 403, 'Access denied', 'invalid token');
		}
		$request = $request->withAttribute('user', $user);
		return $next($request, $response);
	}
}
