<?php

namespace Newsman\Export\Order;

use Newsman\Util\Telephone;

/**
 * Class Export Order Mapper
 *
 * @class \Newsman\Export\Order\Mapper
 */
class Mapper extends \Newsman\Nzmbase {
	/**
	 * Telephone util
	 *
	 * @var Telephone
	 */
	protected $telephone;

	/**
	 * Class construct
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->telephone = new Telephone($registry);
	}

	/**
	 * To array
	 *
	 * @param array $order OpenCart Order data.
	 * @param array $products OpenCart Order products.
	 * @param array $totals OpenCart Order totals.
	 *
	 * @return array Returns ['details' => [], 'products' => []].
	 */
	public function toArray($order, $products, $totals) {
		$shipping_amount = 0;
		$discount = 0;
		$discount_code = '';

		foreach ($totals as $total) {
			switch ($total['code']) {
				case 'shipping':
					$shipping_amount += $total['value'];
					break;
				case 'coupon':
				case 'voucher':
				case 'reward':
					$discount += abs($total['value']);
					// Try to extract the code if it's in the title (OpenCart often puts it there in brackets)
					if (empty($discount_code) && preg_match('/\(([^)]+)\)/', $total['title'], $matches)) {
						if (empty($discount_code)) {
							$discount_code = $matches[1];
						} else {
							$discount_code .= ',' . $matches[1];
						}
					}
					break;
			}
		}

		$order_status = '';
		if (isset($order['order_status'])) {
			$order_status = strtolower($order['order_status']);
		} else if (isset($order['order_status_name'])) {
			$order_status = strtolower($order['order_status_name']);
		}

		$details = array(
			'order_no'      => $order['order_id'],
			'lastname'      => $order['lastname'],
			'firstname'     => $order['firstname'],
			'email'         => $order['email'],
			'phone'         => $this->telephone->clean($order['telephone']),
			'status'        => $order_status,
			'created_at'    => $order['date_added'],
			'discount_code' => $discount_code,
			'discount'      => (float)$discount,
			'shipping'      => (float)$shipping_amount,
			'rebates'       => 0,
			'fees'          => 0,
			'total'         => round((float)$order['total'], 2),
			'currency'      => $order['currency_code'],
		);

		$products_data = array();
		foreach ($products as $item) {
			$products_data[] = array(
				'id'             => (string)$item['product_id'],
				'quantity'       => (int)$item['quantity'],
				'price'          => round(((float)$item['price'] + (float)$item['tax']), 2),
				'variation_code' => '',
			);
		}

		$return = array(
			'details'  => $details,
			'products' => $products_data,
		);

		return $return;
	}
}
