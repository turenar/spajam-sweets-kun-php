<?php

use Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->post('/review/{id:\d+}/likes', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
	$id = $args['id'];
	/** @var \ORM\User $user */
	$user = $request->getAttribute('user');

	for ($i = 0; $i < TRANSACTION_PATIENCE; $i++) {
		$result = transaction(function () use ($response, $user, $id) {
			$review = \ORM\ReviewQuery::create()
				->filterByReviewId($id)
				->findOne();
			if ($review === null) {
				return get_renderer()->renderAsError($response, 404, 'Not Found', '指定したレビューが見つかりません');
			}

			$already_liked = \ORM\LikesQuery::create()
				->filterByUserId($user->getUserId())
				->filterByReviewId($review->getReviewId())
				->exists();
			if (!$already_liked) {
				$atomic_rewritten = \ORM\ReviewQuery::create()
					->filterByReviewId($review->getReviewId())
					->filterByLike($review->getLike())
					->update(['Like' => $review->getLike() + 1]);
				if (!$atomic_rewritten) {
					return null;
				}
				$like = new \ORM\Likes();
				$like
					->setUserId($user->getUserId())
					->setReviewId($review->getReviewId())
					->save();
			}
			return get_renderer()->render($response, []);
		});
		if ($result !== null) {
			return $result;
		}
	}
	return get_renderer()->renderAsError($response, 503, 'Transaction Failure', 'しばらく待ってからもう一度お試しください。');
})->add(new AuthMiddleware())->setArgument('validator.basePath', 'review/likes');
