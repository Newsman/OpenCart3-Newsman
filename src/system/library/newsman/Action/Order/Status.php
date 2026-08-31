<?php

namespace Newsman\Action\Order;

/**
 * Send order status with Newsman API action
 *
 * @class \Newsman\Action\Order\Status
 */
class Status extends \Newsman\Nzmbase {
	/**
	 * @var \ModelCheckoutOrder
	 */
	protected $checkout_order;

	/**
	 * Last status sent to Newsman during this request, keyed by order ID.
	 *
	 * An order added through the store API reports its status twice in the same
	 * request: once when the order row is created and again when api/order/add
	 * writes the first history row. Only a repeat of the status last sent for
	 * that order is dropped, so a genuine change back and forth still reports.
	 *
	 * @var array
	 */
	protected static $sent = array();

	/**
	 * Constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->registry->get('load')->model('checkout/order');
		$this->checkout_order = $this->registry->get('model_checkout_order');
	}

	/**
	 * Execute
	 *
	 * @param int      $order_id Order ID.
	 * @param int|bool $order_status_id Status to report, or false to use the one stored on the order.
	 * @param bool     $is_new
	 *
	 * @return void
	 */
	public function execute($order_id, $order_status_id = false, $is_new = false) {
		if (!$this->config->isEnabledWithApi()) {
			return;
		}

		try {
			$order_info = $this->checkout_order->getOrder($order_id);

			if (!$order_info) {
				return;
			}

			$store_id = $order_info['store_id'];

			if ($order_status_id !== false) {
				$order_status_name = $this->getOrderStatusName($order_status_id, $order_info['language_id']);
			} else {
				$order_status_id = $order_info['order_status_id'];
				$order_status_name = $order_info['order_status'];
			}

			$mapper = new \Newsman\Export\Order\Status\Mapper($this->registry);
			$order_status = $mapper->map($order_status_id, $order_status_name, $store_id, $is_new);

			if (isset(self::$sent[$order_id]) && self::$sent[$order_id] === $order_status) {
				return;
			}

			$context = new \Newsman\Service\Context\Remarketing\SetPurchaseStatus();
			$context->setListId($this->config->getListId())
				->setOrderId($order_id)
				->setOrderStatus($order_status);

			$purchase = new \Newsman\Service\Remarketing\SetPurchaseStatus($this->registry);
			$purchase->execute($context);

			self::$sent[$order_id] = $order_status;

		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
		}
	}

	/**
	 * Get order status name
	 *
	 * @param int $order_status_id
	 * @param int $language_id
	 *
	 * @return string
	 */
	protected function getOrderStatusName($order_status_id, $language_id) {
		/** @var \stdClass $query */
		$query = $this->registry->get('db')->query("SELECT name FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$language_id . "'");

		return ($query->num_rows) ? $query->row['name'] : '';
	}
}
