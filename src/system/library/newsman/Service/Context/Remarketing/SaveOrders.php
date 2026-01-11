<?php

namespace Newsman\Service\Context\Remarketing;

use Newsman\Service\Context\Store;

/**
 * Class Service Context Save Orders
 *
 * @class \Newsman\Service\Context\Remarketing\SaveOrders
 */
class SaveOrders extends Store {
	/**
	 * Orders
	 *
	 * @var array
	 */
	protected $orders;

	/**
	 * Set orders
	 *
	 * @param array $orders Orders.
	 *
	 * @return $this
	 */
	public function setOrders($orders) {
		$this->orders = $orders;

		return $this;
	}

	/**
	 * Get orders
	 *
	 * @return array
	 */
	public function getOrders() {
		return $this->orders;
	}
}
