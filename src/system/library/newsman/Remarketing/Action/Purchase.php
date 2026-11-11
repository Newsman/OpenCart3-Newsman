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

		$run = '_nzm.run';

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
				"'id': '" . $this->escapeJs($product['id']) . "'," .
				"'name': '" . $this->escapeJs($product['name']) . "'," .
				"'price': '" . $this->escapeJs($product['price']) . "'," .
				"'quantity': '" . $this->escapeJs($product['quantity']) . "'" .
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
		$page_view_js = $page_view->getJs();

		$js = "(function() {\n";
		$js .= $this->getDeliveryGuardJs($order_id);

		if (!empty($email)) {
			$js .= 'var nzmIdentity = {email: "' . $this->escapeJs($email) . '", ';
			if ($this->config->isSendTelephone() && !empty($telephone)) {
				$js .= 'phone: "' . $this->escapeJs($telephone) . '", ';
			}
			$js .= 'first_name: "' . $this->escapeJs($first_name) . '", ' .
				'last_name: "' . $this->escapeJs($last_name) . '"};' . "\n";
		} else {
			$js .= "var nzmIdentity = null;\n";
		}

		$js .= $run . "( 'set', 'currencyCode', '" . $this->escapeJs($currency_code) . "' );\n";

		$js .= "var nzmPurchaseQueued = false;\n" .
			"function nzmSendPurchase() {\n" .
			"if (nzmPurchaseQueued) {\n" .
			"return;\n" .
			"}\n" .
			"nzmPurchaseQueued = true;\n" .
			"try { nzmWatchDelivery(0); } catch (e) {}\n" .
			$products_event . "\n" .
			$run . "('ec:setAction', 'purchase', " . json_encode($order_data) . ");\n" .
			$page_view_js . "\n" .
			"}\n";

		// Reload of a success page whose purchase Newsman already received.
		$js .= "if (nzmAlreadySent) {\n" .
			"if (nzmIdentity) {\n" .
			"_nzm.identify(nzmIdentity);\n" .
			"}\n" .
			$page_view_js . "\n" .
			"return;\n" .
			"}\n";

		// The tracker drops a purchase from a visitor without a tracking id, so a
		// visitor not identified yet sends it once identify has stored the id.
		$js .= "if (!nzmIdentity) {\n" .
			"setTimeout(nzmSendPurchase, 1000);\n" .
			"} else if (/(?:^|;\\s*)_nzm_id_[^=]*=[^;]/.test(document.cookie)) {\n" .
			"_nzm.identify(nzmIdentity);\n" .
			"setTimeout(nzmSendPurchase, 1000);\n" .
			"} else {\n" .
			"_nzm.identify(nzmIdentity, undefined, nzmSendPurchase);\n" .
			"setTimeout(nzmSendPurchase, 5000);\n" .
			"}\n" .
			"})();";

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

	/**
	 * Remembers in localStorage the orders whose purchase Newsman received, so a
	 * reload of the success page does not send the purchase again.
	 *
	 * An order counts as received only after the tracker confirmed a purchase
	 * (_nzm.onPurchase, called on an OK response) while the request for this
	 * order, issued on this page, is no longer waiting in the tracker's
	 * nzm_retry queue. Anything less (tracker blocked, request failed, consent
	 * denied, storage unavailable) leaves the order unmarked and the purchase is
	 * sent again on the next view.
	 *
	 * @param int|string|null $order_id
	 *
	 * @return string
	 */
	protected function getDeliveryGuardJs($order_id) {
		$js = <<<'JS'
var nzmOrderId = '__ORDER_ID__';
var nzmSentKey = 'newsman_purchases_sent';
function nzmSentOrders() {
	try {
		var sent = JSON.parse(window.localStorage.getItem(nzmSentKey) || '{}');
		return sent && typeof sent === 'object' ? sent : {};
	} catch (e) {
		return {};
	}
}
function nzmIsOrderPurchase(url) {
	var u = String(url);
	for (var i = 0; i < 3; i++) {
		try {
			var decoded = decodeURIComponent(u);
			if (decoded === u) {
				break;
			}
			u = decoded;
		} catch (e) {
			break;
		}
	}
	return u.indexOf('p[pa]=purchase') !== -1 && new RegExp('[?&]p\\[ti\\]=' + nzmOrderId + '(&|$)').test(u);
}
function nzmPurchaseDelivered() {
	var requests = window._nzm && window._nzm.stats && window._nzm.stats.requests;
	var issued = false;
	for (var i = 0; requests && i < requests.length; i++) {
		if (nzmIsOrderPurchase(requests[i])) {
			issued = true;
		}
	}
	if (!issued) {
		return false;
	}
	var pending = JSON.parse(window.localStorage.getItem('nzm_retry') || '[]');
	if (!Array.isArray(pending)) {
		return false;
	}
	for (var j = 0; j < pending.length; j++) {
		if (!pending[j] || typeof pending[j].url !== 'string' || nzmIsOrderPurchase(pending[j].url)) {
			return false;
		}
	}
	return true;
}
function nzmMarkSent() {
	try {
		if (!nzmPurchaseDelivered()) {
			return;
		}
		var sent = nzmSentOrders();
		var ids = [];
		sent[nzmOrderId] = Date.now();
		for (var id in sent) {
			if (Object.prototype.hasOwnProperty.call(sent, id)) {
				ids.push(id);
			}
		}
		ids.sort(function (a, b) { return sent[b] - sent[a]; });
		for (var k = 50; k < ids.length; k++) {
			delete sent[ids[k]];
		}
		window.localStorage.setItem(nzmSentKey, JSON.stringify(sent));
	} catch (e) {}
}
function nzmWatchDelivery(attempt) {
	var nzm = window._nzm;
	// Until t.js loads _nzm is the stub array, which the tracker then replaces.
	if (!nzm || Array.isArray(nzm)) {
		if (attempt < 600) {
			setTimeout(function () { nzmWatchDelivery(attempt + 1); }, 100);
		}
		return;
	}
	var previous = nzm.onPurchase;
	nzm.onPurchase = function () {
		nzmMarkSent();
		if (typeof previous === 'function') {
			return previous.apply(this, arguments);
		}
	};
}
var nzmAlreadySent = !!nzmSentOrders()[nzmOrderId];

JS;

		return str_replace('__ORDER_ID__', (string)(int)$order_id, $js);
	}
}
