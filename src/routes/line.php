<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

define('CRYPT_METHOD', 'AES-128-ECB'); // コピペミスを検知するためだけだが、もっと強いほうがいい？
define('CRYPT_KEY', getenv('FF_CRYPT_KEY') ?: '~x.SrFBeKu-/v5s;.?K[!K-yUA3y\GVS');
define('LINE_CHANNEL_SECRET', getenv('LINE_CHANNEL_SECRET'));
define('LINE_CHANNEL_ACCESS_TOKEN', getenv('LINE_CHANNEL_ACCESS_TOKEN'));

$app->post('/line', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$channelSecret = LINE_CHANNEL_SECRET; // Channel secret string
	$httpRequestBody = $request->getBody()->getContents(); // Request body string
	$hash = hash_hmac('sha256', $httpRequestBody, $channelSecret, true);
	$signature = base64_encode($hash);
	// Compare X-Line-Signature request header string and the signature
	// getHeader() returns array
	if ($request->getHeader('X-Line-Signature') !== [$signature]) {
		$this->logger->error('line signature verification failed',
			['expected' => $request->getHeader('X-Line-Signature'), 'actual' => $signature]);
		return get_renderer()->renderAsError($response, 400, 'Bad Request', 'Line signature verification failed');
	}

	$url = 'https://api.line.me/v2/bot/message/reply';
	$channel_access_token = LINE_CHANNEL_ACCESS_TOKEN;
	$headers = array(
		'Content-type: application/json',
		"Authorization: Bearer {$channel_access_token}"
	);

	$json_string = file_get_contents('php://input');
	$json_object = json_decode($json_string);

	//var_dump($json_object);
	$this->logger->addDebug("json_string" . $json_string);

	$result = "";
	if (isset($json_object->events)) {
		foreach ($json_object->events as $event) {
			$token = $event->replyToken;

			$post = null;
			$this->logger->addDebug("token" . $token);
			/*if ($event->type === 'message') {
				$post = array(
					'replyToken' => $token,
					'messages' => array(
						array(
							'type' => 'text',
							'text' => $event->source->userId
						)
					)
				);
			}*/

			if ($event->source->type === 'group' && strpos($event->message->text, 'familytoken') !== false) {
				$redis = new Redis();
				$redis->connect("127.0.0.1", 6379);
				$value = $redis->lRange('familyTokens', 0, -1);
				$this->logger->debug($value);
				foreach ($value as $id) {
					if ($id === $event->source->groupId) {
						$familyToken = explode(':', $event->message->text)[1];
						$family = \ORM\FamilyQuery::create()->filterByToken($familyToken)->findOne();
						if ($family === null) {
							$post = array(
								'replyToken' => $token,
								'messages' => array(
									array(
										'type' => 'text',
										'text' => 'Invalid Token'
									)
								)
							);
							break;
						}
						$family->setRoomId($id);
						$family->save();

						$post = array(
							'replyToken' => $token,
							'messages' => array(
								array(
									'type' => 'text',
									'text' => 'アプリとの連携を設定しました'
								)
							)
						);
						break;
					}
				}
			}

			if ($event->type === 'beacon') {
				$lineId = $event->source->userId;
				$this->logger->addDebug("lineId" . $lineId);
				$user = \ORM\UserQuery::create()->filterByLineId($lineId)->findOne();
				$item = \ORM\UserItemQuery::create()
					->filterByFamilyId((int)$user->getFamilyId())
					->orderByExpireDate()
					->findOne();

				if ($item !== null) {
					$this->logger->addDebug("item" . $item->getItemName());
					$message_text = sprintf('%s の賞味期限は %s です', $item->getItemName(), $item->getExpireDate()->format('Y-m-d'));
					$this->logger->addDebug("messages" . $message_text);

					$post = array(
						'replyToken' => $token,
						'messages' => array(
							array(
								'type' => 'text',
								'text' => $message_text
							)
						)
					);
				} else {
					$post = null;
				}
			}

			if ($event->type === 'join') {
				$msg = openssl_encrypt($event->source->groupId, CRYPT_METHOD, CRYPT_KEY);
				$encrypted = $msg;

				$post = array(
					'replyToken' => $token,
					'messages' => array(
						array(
							'type' => 'text',
							'text' => 'このURLからアプリを起動して、LINE連携を完了させてください。 freshfridge://?group_id=' . urlencode($encrypted)
						)
					)
				);
			}
			if ($event->type === 'follow') {
				$msg = openssl_encrypt($event->source->userId, CRYPT_METHOD, CRYPT_KEY);
				$encrypted = $msg;

				$post = array(
					'replyToken' => $token,
					'messages' => array(
						array(
							'type' => 'text',
							'text' => 'このURLからアプリを起動して、LINE連携を完了させてください。 freshfridge://?user_id=' . urlencode($encrypted)
						)
					)
				);
			}

			if ($post !== null) {
				$this->logger->addDebug("post" . json_encode($post));

				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				$result = curl_exec($curl);
				curl_close($curl);

				$this->logger->addDebug("result" . $result);
			}

			$data = [
				"status" => "OK",
				"result" => $result
			];

			return $response->withJson($data);
		}
	}
	return $response;
});

$app->post('/line/user', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$line_user_id_encrypted = $request->getParsedBody()['line_user_id'];
	$line_user_id = openssl_decrypt($line_user_id_encrypted, CRYPT_METHOD, CRYPT_KEY);
	if ($line_user_id === FALSE) {
		return get_renderer()->renderAsError($response, 400, 'Bad Request', 'invalid line_user_id');
	}
	/** @var \ORM\User $user */
	$user = $request->getAttribute('user');
	$user
		->setLineId($line_user_id)
		->save();

	return get_renderer()->render($response);
})->add(new \middleware\AuthMiddleware())->add(new \middleware\RequestValidateMiddleware());

$app->post('/line/group', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$line_group_id_encrypted = $request->getParsedBody()['line_group_id'];
	$line_group_id = openssl_decrypt($line_group_id_encrypted, CRYPT_METHOD, CRYPT_KEY);
	if ($line_group_id === FALSE) {
		return get_renderer()->renderAsError($response, 400, 'Bad Request', 'invalid line_group_id');
	}
	/** @var \ORM\User $user */
	$user = $request->getAttribute('user');
	$user
		->getFamily()
		->setRoomId($line_group_id)
		->save();

	return get_renderer()->render($response);
})->add(new \middleware\AuthMiddleware())->add(new \middleware\RequestValidateMiddleware());
