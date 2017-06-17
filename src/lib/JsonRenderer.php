<?php

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Body;

/**
 * Class JsonRenderer
 *
 * Render PHP view scripts into a PSR-7 Response object
 */
class JsonRenderer
{
	/**
	 * JsonRenderer constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Render a template
	 *
	 * @param ResponseInterface $response
	 * @param array $data
	 *
	 * @return ResponseInterface
	 */
	public function render(ResponseInterface $response, array $data = [])
	{
		$status = $response->getStatusCode();
		$data['meta'] = ['status' => $status];

		$response->getBody()->write(json_encode($data));
		return $response;
	}

	/**
	 * Render error
	 *
	 * @param ResponseInterface $response
	 * @param int $status_code
	 * @param string $message
	 * @param string $reason
	 * @param mixed $extra json passable-object
	 * @return ResponseInterface
	 */
	public function renderAsError(ResponseInterface $response, $status_code, $message, $reason, $extra = null)
	{
		$response = $response->withStatus($status_code);
		$data['meta'] = [
			'status' => $status_code,
			'message' => $message,
			'reason' => $reason];
		if ($extra !== null) {
			$data['meta']['extra'] = $extra;
		}

		$body = new Body(fopen('php://temp', 'r+'));
		$body->write(json_encode($data));
		return $response->withBody($body);
	}
}
