<?php

namespace Newsman\Service\Context\Remarketing;

use Newsman\Service\Context\Store;

/**
 * Class Service Context Order Set Purchase Status
 *
 * @class \Newsman\Service\Context\Remarketing\SetPurchaseStatus
 */
class SetPurchaseStatus extends Store {
	/**
	 * Order ID
	 *
	 * @var string
	 */
	protected $order_id;

	/**
	 * Order status
	 *
	 * @var string
	 */
	protected $order_status;

	/**
	 * Set order ID
	 *
	 * @param string $order_id Order ID.
	 *
	 * @return $this
	 */
	public function setOrderId($order_id) {
		$this->order_id = $order_id;

		return $this;
	}

	/**
	 * Get order ID
	 *
	 * @return string
	 */
	public function getOrderId() {
		return $this->order_id;
	}

	/**
	 * Set order status
	 *
	 * @param string $order_status Order status.
	 *
	 * @return $this
	 */
	public function setOrderStatus($order_status) {
		$this->order_status = $order_status;

		return $this;
	}

	/**
	 * Get order status
	 *
	 * @return string
	 */
	public function getOrderStatus() {
		return $this->order_status;
	}
}
