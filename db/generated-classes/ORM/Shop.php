<?php

namespace ORM;

use ORM\Base\Shop as BaseShop;

/**
 * Skeleton subclass for representing a row from the 'shop' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Shop extends BaseShop
{

	public function render($reviews = null)
	{
		return [
			'shop_id' => $this->getShopId(),
			'name' => $this->getName(),
			'address' => $this->getAddress(),
			'latitude' => $this->getLatitude(),
			'longitude' => $this->getLongitude(),

			'review' => render_as_json($reviews),
		];
	}
}
