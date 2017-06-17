<?php

namespace ORM;

use ORM\Base\Review as BaseReview;

/**
 * Skeleton subclass for representing a row from the 'review' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Review extends BaseReview
{

	public function render()
	{
		return [
			'review_id' => $this->getReviewId(),
			'shop_id' => $this->getShopId(),
			'user_id' => $this->getUserId(),
			'rating' => $this->getRating(),
			'review_text' => $this->getReviewText(),
			'sweet_type' => $this->getSweetType(),
			'like' => $this->getLike(),
			'latitude'=>$this->getLatitude(),
			'longitude' => $this->getLongitude(),
		];
	}
}
