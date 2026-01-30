<?php

/**
 * Newsman Controller
 *
 * @property \Newsman\Nzmloader $nzmloader
 * @property \Newsman\Nzmconfig $nzmconfig
 * @property \Newsman\Nzmlogger $nzmlogger
 * @property \Loader           $load
 * @property \Request          $request
 * @property \Response         $response
 * @property \Session          $session
 * @property \Language         $language
 * @property \Url              $url
 * @property \Config           $config
 * @property \Cart\Customer    $customer
 * @property \Cart\Cart        $cart
 * @property \DB               $db
 * @property \Event            $event
 */
class ControllerExtensionmoduleNewsman extends Controller {
	/**
	 * Index action
	 */
	public function index() {
		$data = array();

		$this->load->library('newsman/nzmloader');
		$this->nzmloader->autoload();

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && !empty($this->request->post['newsman_events'])) {
			$webhooks = new \Newsman\Webhooks($this->registry);
			$webhooks->execute($this->request->post['newsman_events']);
		} else {
			$router = new \Newsman\Export\Router($this->registry);
			$router->execute();
		}

		return $this->load->view('extension/module/newsman', $data);
	}

	/**
	 * Get cart items AJAX action.
	 */
	public function cart() {
		$items = array();
		$cart = $this->cart->getProducts();
		foreach ($cart as $cart_item) {
			$items[] = array(
				"id"       => $cart_item['product_id'],
				"name"     => $cart_item["name"],
				"price"    => $cart_item["price"],
				"quantity" => $cart_item['quantity']
			);
		}

		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false); // Older IE browsers
		header("Pragma: no-cache");
		header('Content-Type:application/json');
		echo json_encode($items, JSON_PRETTY_PRINT);
		exit;
	}

	/**
	 * @param string $route
	 * @param array  $data
	 *
	 * @return void
	 */
	public function eventAccountNewsletterBefore($route, $data) {
		if (!$this->customer->isLogged()) {
			return;
		}

		if (!($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['newsletter']))) {
			return;
		}

		try {
			$new = $this->request->post['newsletter'];
			if (!$this->customer->getNewsletter() && $new) {
				$this->subscribeCustomer(
					$this->customer->getEmail(),
					$this->customer->getFirstName(),
					$this->customer->getLastName(),
					$this->customer->getTelephone(),
					(int)$this->config->get('config_store_id')
				);
			} elseif ($this->customer->getNewsletter() && !$new) {
				$this->unsubscribeCustomer($this->customer->getEmail(), (int)$this->config->get('config_store_id'));
			}
		} catch (\Exception $e) {
			$this->load->library('newsman/nzmlogger');
			$this->nzmlogger->logException($e);
		}
	}

	/**
	 * Event checkout register save after
	 *
	 * @param string $route
	 * @param array  $data
	 * @param string $output
	 *
	 * @return void
	 */
	public function eventCheckoutRegisterSaveAfter($route, $data, &$output) {
		if (!isset($this->request->post['newsletter']) || !$this->request->post['newsletter']) {
			return;
		}

		try {
			$this->subscribeCustomer(
				$this->request->post['email'],
				$this->request->post['firstname'],
				$this->request->post['lastname'],
				isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '',
				(int)$this->config->get('config_store_id')
			);
		} catch (\Exception $e) {
			$this->load->library('newsman/nzmlogger');
			$this->nzmlogger->logException($e);
		}
	}

	/**
	 * Event account register after
	 *
	 * @param string $route
	 * @param array  $args
	 * @param int    $output Customer ID
	 *
	 * @return void
	 */
	public function eventAccountRegisterAfter($route, $args, $output) {
		$this->load->library('newsman/nzmconfig');

		if (!(isset($this->request->post['newsletter']) && $this->request->post['newsletter'])) {
			return;
		}

		try {
			$this->subscribeCustomer(
				$this->request->post['email'],
				$this->request->post['firstname'],
				$this->request->post['lastname'],
				isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '',
				(int)$this->config->get('config_store_id')
			);
		} catch (\Exception $e) {
			$this->load->library('newsman/nzmlogger');
			$this->nzmlogger->logException($e);
		}
	}

	/**
	 * Event checkout guest save after
	 *
	 * @param string $route
	 * @param array  $data
	 * @param string $output
	 *
	 * @return void
	 */
	public function eventCheckoutGuestSaveAfter($route, $data, &$output) {
		$this->load->library('newsman/nzmconfig');

		if (!$this->nzmconfig->isCheckoutNewsletter()) {
			return;
		}

		if (!(isset($this->request->post['newsletter']) && $this->request->post['newsletter'])) {
			return;
		}

		try {
			$this->subscribeCustomer(
				$this->request->post['email'],
				$this->request->post['firstname'],
				$this->request->post['lastname'],
				isset($this->request->post['telephone']) ? $this->request->post['telephone'] : '',
				(int)$this->config->get('config_store_id')
			);
		} catch (\Exception $e) {
			$this->load->library('newsman/nzmlogger');
			$this->nzmlogger->logException($e);
		}
	}

	/**
	 * Event checkout guest before
	 *
	 * @param string $route
	 * @param array  $data
	 *
	 * @return void
	 */
	public function eventCheckoutGuestBefore($route, &$data) {
		$this->load->library('newsman/nzmconfig');

		if (!$this->nzmconfig->isCheckoutNewsletter()) {
			return;
		}

		$this->load->language('extension/module/newsman');

		$label = $this->nzmconfig->getCheckoutNewsletterLabel();
		if (empty($label)) {
			$label = $this->language->get('entry_newsletter');
		}

		$data['entry_newsletter'] = $label;
		$data['newsman_newsletter_checked'] = $this->nzmconfig->isCheckoutNewsletterDefault();
	}

	/**
	 * Event checkout guest after
	 *
	 * @param string $route
	 * @param array  $data
	 * @param string $output
	 *
	 * @return void
	 */
	public function eventCheckoutGuestAfter($route, $data, &$output) {
		$this->load->library('newsman/nzmconfig');

		if (!$this->nzmconfig->isCheckoutNewsletter()) {
			return;
		}

		$search = '<div class="buttons">';
		$html = '<div class="checkbox">' . PHP_EOL;
		$html .= '  <label>' . PHP_EOL;
		$checked = (isset($data['newsman_newsletter_checked']) && $data['newsman_newsletter_checked']) ? 'checked="checked"' : '';
		$html .= '    <input type="checkbox" name="newsletter" value="1" ' . $checked . ' />' . PHP_EOL;
		$html .= '    ' . (isset($data['entry_newsletter']) ? $data['entry_newsletter'] : 'I wish to subscribe to the newsletter.') . '</label>' . PHP_EOL;
		$html .= '</div>' . PHP_EOL;

		$output = str_replace($search, $html . $search, $output);
	}

	/**
	 * Event checkout order add after
	 *
	 * @param string $route
	 * @param array  $data
	 * @param int    $output Order ID
	 *
	 * @return void
	 */
	public function eventCheckoutOrderAddAfter($route, $data, $output) {
		$this->autoloadNewsman();
		$this->load->library('newsman/nzmlogger');

		$order_id = $output;

		if (empty($order_id)) {
			return;
		}

		try {
			$action = new \Newsman\Action\Order\Save($this->registry);
			$action->execute($order_id, true);

			$status_action = new \Newsman\Action\Order\Status($this->registry);
			$status_action->execute($order_id, false, true);
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);
		}
	}

	/**
	 * Event API order history before
	 *
	 * @param string $route
	 * @param array  $data
	 *
	 * @return void
	 */
	public function eventApiOrderHistoryBefore($route, $data) {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}

		if (!isset($this->request->get['order_id'])) {
			return;
		}

		$order_id = $this->request->get['order_id'];

		if (!isset($this->request->post['order_status_id'])) {
			return;
		}

		$this->load->library('newsman/nzmlogger');
		try {
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($order_id);

			if (!$order_info) {
				return;
			}

			if ($this->request->post['order_status_id'] != $order_info['order_status_id']) {
				$this->autoloadNewsman();

				$status_action = new \Newsman\Action\Order\Status($this->registry);
				$status_action->execute($order_id, $this->request->post['order_status_id'], true);
			}
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);
		}
	}

	/**
	 * Event API order edit after
	 *
	 * @param string $route
	 * @param array  $data
	 * @param array  $output
	 *
	 * @return void
	 */
	public function eventApiOrderEditAfter($route, $data, &$output) {
		if ($this->request->server['REQUEST_METHOD'] != 'POST') {
			return;
		}

		if (!isset($this->request->get['order_id'])) {
			return;
		}

		$order_id = $this->request->get['order_id'];

		$this->autoloadNewsman();
		$this->load->library('newsman/nzmlogger');

		try {
			$action = new \Newsman\Action\Order\Save($this->registry);
			$action->execute($order_id);
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);
		}
	}

	/**
	 * Helper method to autoload Newsman libraries.
	 *
	 * @return void
	 */
	protected function autoloadNewsman() {
		$this->load->library('newsman/nzmloader');
		$this->nzmloader->autoload();
	}

	/**
	 * Helper method to subscribe a customer to Newsman.
	 *
	 * @param string $email
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $telephone
	 * @param int    $store_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function subscribeCustomer($email, $firstname, $lastname, $telephone, $store_id) {
		$this->autoloadNewsman();
		$this->load->library('newsman/nzmconfig');

		$email_action = new \Newsman\Action\Subscribe\Email($this->registry);

		$properties = array();

		if ($this->nzmconfig->isSendTelephone($store_id)) {
			if (!empty($telephone)) {
				$properties['phone'] = $telephone;
			}
		}

		$options = array();
		$segment_id = $this->nzmconfig->getSegmentId($store_id);
		if (!empty($segment_id)) {
			$options['segments'] = array($segment_id);
		}

		$email_action->execute(
			$email,
			$firstname,
			$lastname,
			$properties,
			$options,
			$store_id
		);
	}

	/**
	 * Helper method to unsubscribe a customer from Newsman.
	 *
	 * @param string $email
	 * @param int    $store_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function unsubscribeCustomer($email, $store_id = 0) {
		$this->autoloadNewsman();

		$email_action = new \Newsman\Action\Subscribe\Email($this->registry);
		$email_action->unsubscribe($email, $store_id);
	}
}
