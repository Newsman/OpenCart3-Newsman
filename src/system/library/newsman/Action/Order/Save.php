<?php

namespace Newsman\Action\Order;

/**
 * Save order with Newsman API action
 *
 * @class \Newsman\Action\Order\Save
 */
class Save extends \Newsman\Nzmbase {
	/**
	 * @var \ModelCheckoutOrder
	 */
	protected $checkout_order;

	/**
	 * Constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->registry->load->model('checkout/order');
		$this->checkout_order = $this->registry->get('model_checkout_order');
	}

	/**
	 * Execute
	 *
	 * @param int  $order_id Order ID.
	 * @param bool $is_new
	 *
	 * @return void
	 */
	public function execute($order_id, $is_new = false) {
		if (!$this->config->isEnabledWithApi()) {
			return;
		}

		try {
			$order_info = $this->checkout_order->getOrder($order_id);

			if (!$order_info) {
				return;
			}

			$status_mapper = new \Newsman\Export\Order\Status\Mapper($this->registry);
			$order_info['order_status'] = $status_mapper->map($order_info['order_status_id'], $order_info['order_status'], $order_info['store_id'], $is_new);

			$order_products = $this->checkout_order->getOrderProducts($order_id);
			$order_totals = $this->checkout_order->getOrderTotals($order_id);

			$mapper = new \Newsman\Export\Order\Mapper($this->registry);
			$order_data = $mapper->toArray($order_info, $order_products, $order_totals);

			$context = new \Newsman\Service\Context\Remarketing\SaveOrder();
			$context->setListId($this->config->getListId())
				->setOrderDetails($order_data['details'])
				->setOrderProducts($order_data['products']);

			$save_order = new \Newsman\Service\Remarketing\SaveOrder($this->registry);
			$save_order->execute($context);

		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());
		}
	}
}
