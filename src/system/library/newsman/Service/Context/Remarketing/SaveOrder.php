<?php

namespace Newsman\Service\Context\Remarketing;

use Newsman\Service\Context\Store;

/**
 * Class Service Context Save Order
 *
 * @class \Newsman\Service\Context\Remarketing\SaveOrder
 */
class SaveOrder extends Store {
	/**
	 * Order details
	 *
	 * @var array
	 */
	protected $order_details;

	/**
	 * Order products
	 *
	 * @var array
	 */
	protected $order_products;

	/**
	 * Set order details
	 *
	 * @param array $order_details Order details.
	 *
	 * @return $this
	 */
	public function setOrderDetails($order_details) {
		$this->order_details = $order_details;

		return $this;
	}

	/**
	 * Get order details
	 *
	 * @return array
	 */
	public function getOrderDetails() {
		return $this->order_details;
	}

	/**
	 * Set order products
	 *
	 * @param array $order_products Order products.
	 *
	 * @return $this
	 */
	public function setOrderProducts($order_products) {
		$this->order_products = $order_products;

		return $this;
	}

	/**
	 * Get order products
	 *
	 * @return array
	 */
	public function getOrderProducts() {
		return $this->order_products;
	}
}
