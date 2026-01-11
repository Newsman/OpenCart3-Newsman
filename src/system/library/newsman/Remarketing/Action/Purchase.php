<?php

namespace Newsman\Remarketing\Action;

/**
 * Class Purchase Action
 */
class Purchase extends \Newsman\Remarketing\Action\AbstractAction {
	/**
	 * @param array $order
	 * @param array $order_products
	 *
	 * @return string
	 */
	public function getJs($order, $order_products) {
		if (empty($order)) {
			$page_view = new \Newsman\Remarketing\Action\PageView($this->registry);
			$page_view->setEvent($this->getEvent());

			return $page_view->getJs();
		}

		$run = $this->escapeHtml($this->getConfig()->getJsTrackRunFunc());

		$email = $order['email'];
		$first_name = $order['firstname'];
		$last_name = $order['lastname'];
		$telephone = $order['telephone'];
		$currency_code = $order['currency_code'];

		$order_id = (!empty($order['order_id'])) ? $order['order_id'] : null;
		$products = [];
		if (!empty($order_products)) {
			foreach ($order_products as $product) {
				$products[] = $this->getProduct($order_id, $product);
			}
		}

		$products_event = '';
		foreach ($products as $product) {
			$products_event .=
				$run . "( 'ec:addProduct', {" .
				"'id': '" . $this->escapeHtml($product['id']) . "'," .
				"'name': '" . $this->escapeHtml($product['name']) . "'," .
				"'price': '" . $this->escapeHtml($product['price']) . "'," .
				"'quantity': '" . $this->escapeHtml($product['quantity']) . "'" .
				"} ); ";
		}

		$order_data = [];
		if (!empty($order_id)) {
			$order_data = [
				"id"          => $this->escapeHtml($order_id),
				"affiliation" => $this->escapeHtml($order['store_name']),
				"revenue"     => $this->escapeHtml($order['total']),
				"tax"         => 0,
				"shipping"    => 0,
				"currency"    => $this->escapeHtml($currency_code)
			];
		}

		$this->getEvent()->trigger(
			'newsmanremarketing/remarketing_action_purchase/before',
			array(
				&$order,
				&$order_products,
				&$order_data,
				&$products,
				&$email,
				&$telephone,
				&$first_name,
				&$last_name,
				&$currency_code
			)
		);

		$page_view = new \Newsman\Remarketing\Action\PageView($this->registry);
		$page_view->setEvent($this->getEvent());

		$js = '_nzm.identify({email: "' . $this->escapeHtml($email) . '", ';
		if ($this->config->isSendTelephone() && !empty($telephone)) {
			$js .= 'phone: "' . $this->escapeHtml($telephone) . '", ';
		}
		$js .= 'first_name: "' . $this->escapeHtml($first_name) . '", ' .
			'last_name: "' . $this->escapeHtml($last_name) . '"});';

		$js .= ' ' . $run . "( 'set', 'currencyCode', '" . $this->escapeHtml($currency_code) . "' ); ";

		$js .= "setTimeout(function() { " .
			$products_event . " " .
			$run . "('ec:setAction', 'purchase', " . json_encode($order_data) . "); " .
			$page_view->getJs() . "
			}, 1000);";

		$this->getEvent()->trigger(
			'newsmanremarketing/remarketing_action_purchase/after',
			array(
				&$js,
				&$order,
				&$order_products,
				&$order_data,
				&$products
			)
		);

		return $js;
	}
}
