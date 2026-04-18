<?php

/**
 * Class ControllerExtensionAnalyticsNewsmanremarketing
 *
 * @property \Newsman\Nzmconfig    $nzmconfig
 * @property \Loader               $load
 * @property \Request              $request
 * @property \Response             $response
 * @property \Session              $session
 * @property \Language             $language
 * @property \Url                  $url
 * @property \Config               $config
 * @property \Document             $document
 * @property \Cart\Customer        $customer
 * @property \Cart\Cart            $cart
 * @property \Cart\Tax             $tax
 * @property \DB                   $db
 * @property \Event                $event
 * @property \ModelCatalogProduct  $model_catalog_product
 * @property \ModelCatalogCategory $model_catalog_category
 * @property \ModelCheckoutOrder   $model_checkout_order
 */
class ControllerExtensionAnalyticsNewsmanremarketing extends Controller {
	/**
	 * @param \Registry $registry
	 *
	 * @throws \Exception
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->load->library('newsman/nzmconfig');
	}

	/**
	 * @return string
	 */
	public function index() {
		if (!$this->nzmconfig->isRemarketingActive()) {
			return '';
		}

		$this->load->model('checkout/order');

		$is_add_page_view = true;
		$output = $this->getTrackingScriptJs();
		$output .= $this->getCartJs();
		$output .= $this->getCustomerIdentifyJs();

		switch ($this->getCurrentRoute()) {
			case "product/product":
				$output .= $this->getProductViewJs();
				break;

			case "product/category":
				$output .= $this->getCategoryViewJs();
				break;

			case "checkout/success":
				$is_add_page_view = false;
				$output .= $this->getPurchaseJs();
				break;
		}

		if ($is_add_page_view) {
			$output .= $this->getPageViewJs();
		}

		return $output;
	}

	/**
	 * @return string
	 */
	public function getTrackingScriptJs() {
		$data = array();
		$track = new \Newsman\Remarketing\Script\Track($this->registry);
		$config_js = '';
		$this->event->trigger('newsmanremarketing/script_tracking_config/before', array(&$config_js));
		$track->setJsConfig($config_js);
		$data['tracking_script_js'] = $track->getScript();

		$data['tag_attrib'] = $this->getScripTagAttributes();
		$data['is_anonymize_ip'] = $this->nzmconfig->isAnonymizeIp();

		$no_track_script = '';
		$this->event->trigger('newsmanremarketing/script_tracking_no_track/before', array(&$no_track_script));
		$data['no_track_script'] = $no_track_script;

		$currency_code = $this->session->data['currency'] ?? $this->config->get('config_currency');
		$data['currency_code'] = $track->escapeHtml($currency_code);

		$this->event->trigger('newsmanremarketing/script_tracking_render/before', array(&$data));
		$output = $this->load->view('extension/analytics/newsman/track', $data);
		$this->event->trigger('newsmanremarketing/script_tracking_render/after', array(&$data, &$output));

		return $output;
	}

	/**
	 * @return string
	 */
	public function getCartJs() {
		$data = array('tag_attrib' => $this->getScripTagAttributes());

		$server = ($this->request->server['HTTPS']) ? $this->config->get('config_ssl') : $this->config->get('config_url');
		$data['base_url'] = rtrim($server, '/');

		$data['nzm_time_diff'] = 5000;
		if ($this->getCurrentRoute() === 'checkout/success') {
			$data['nzm_time_diff'] = 1000;
		}

		$template = $this->nzmconfig->isThemeCartCompatibility()
			? 'extension/analytics/newsman/cart'
			: 'extension/analytics/newsman/minicart';

		$this->event->trigger('newsmanremarketing/script_cart_render/before', array(&$data));
		$output = $this->load->view($template, $data);
		$this->event->trigger('newsmanremarketing/script_cart_render/after', array(&$data, &$output));

		return $output;
	}

	/**
	 * @return string
	 */
	public function getPageViewJs() {
		$page_view = new \Newsman\Remarketing\Action\PageView($this->registry);
		$page_view->setEvent($this->event);

		$data = array(
			'page_view_js' => $page_view->getJs(),
			'tag_attrib'   => $this->getScripTagAttributes()
		);

		$this->event->trigger('newsmanremarketing/script_page_view_render/before', array(&$data));
		$output = $this->load->view('extension/analytics/newsman/pageview', $data);
		$this->event->trigger('newsmanremarketing/script_page_view_render/after', array(&$data, &$output));

		return $output;
	}

	/**
	 * @return string
	 */
	public function getCustomerIdentifyJs() {
		if (!$this->customer->isLogged()) {
			return '';
		}

		if ($this->getCurrentRoute() === 'checkout/success') {
			return '';
		}

		$identify = new \Newsman\Remarketing\Action\CustomerIdentify($this->registry);
		$identify->setEvent($this->event);

		$data = array(
			'customer_identify_js' => $identify->getJs($this->customer),
			'tag_attrib'           => $this->getScripTagAttributes()
		);

		$this->event->trigger('newsmanremarketing/script_customer_identify_render/before', array(&$data));
		$output = $this->load->view('extension/analytics/newsman/customeridentify', $data);
		$this->event->trigger('newsmanremarketing/script_customer_identify_render/after', array(&$data, &$output));

		return $output;
	}

	/**
	 * @return string
	 */
	public function getProductViewJs() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');

		$product_view = new \Newsman\Remarketing\Action\ProductView($this->registry);
		$product_view->setEvent($this->event)
			->setDb($this->db)
			->setRequest($this->request)
			->setProductModel($this->model_catalog_product)
			->setCategoryModel($this->model_catalog_category)
			->setCheckoutOrderModel($this->model_checkout_order);

		$data = array(
			'product_view_js' => $product_view->getJs(),
			'tag_attrib'      => $this->getScripTagAttributes()
		);

		$this->event->trigger('newsmanremarketing/script_product_view_render/before', array(&$data));
		$output = $this->load->view('extension/analytics/newsman/productview', $data);
		$this->event->trigger('newsmanremarketing/script_product_view_render/after', array(&$data, &$output));

		return $output;
	}

	/**
	 * @return string
	 */
	public function getCategoryViewJs() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');

		$category_view = new \Newsman\Remarketing\Action\CategoryView($this->registry);
		$category_view->setEvent($this->event)
			->setDb($this->db)
			->setRequest($this->request)
			->setProductModel($this->model_catalog_product)
			->setCategoryModel($this->model_catalog_category)
			->setCheckoutOrderModel($this->model_checkout_order);

		$data = array(
			'category_view_js' => $category_view->getJs(),
			'tag_attrib'       => $this->getScripTagAttributes()
		);

		$this->event->trigger('newsmanremarketing/script_category_view_render/before', array(&$data));
		$output = $this->load->view('extension/analytics/newsman/categoryview', $data);
		$this->event->trigger('newsmanremarketing/script_category_view_render/after', array(&$data, &$output));

		return $output;
	}

	/**
	 * @return string
	 */
	public function getPurchaseJs() {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');

		$purchase = new \Newsman\Remarketing\Action\Purchase($this->registry);
		$purchase->setEvent($this->event)
			->setDb($this->db)
			->setRequest($this->request)
			->setProductModel($this->model_catalog_product)
			->setCategoryModel($this->model_catalog_category)
			->setCheckoutOrderModel($this->model_checkout_order);

		$order_details = array();
		if (isset($this->session->data['ga_orderDetails'])) {
			$order_details = $this->session->data['ga_orderDetails'];
		}
		$order_products = array();
		if (isset($this->session->data['ga_orderProducts'])) {
			$order_products = $this->session->data['ga_orderProducts'];
		}
		$data = array(
			'purchase_js' => $purchase->getJs($order_details, $order_products),
			'tag_attrib'  => $this->getScripTagAttributes()
		);

		$this->event->trigger('newsmanremarketing/script_purchase_render/before', array(&$data));
		$output = $this->load->view('extension/analytics/newsman/purchase', $data);
		$this->event->trigger('newsmanremarketing/script_purchase_render/after', array(&$data, &$output));

		unset($this->session->data['ga_orderDetails']);
		unset($this->session->data['ga_orderProducts']);

		return $output;
	}

	/**
	 * Example: type="text/plain" used for GDPR scripts blocking cookies.
	 *
	 * @return string
	 */
	public function getScripTagAttributes() {
		$script_tag_attributes = '';
		$this->event->trigger(
			'newsmanremarketing/script_tracking_attributes/before',
			array(&$script_tag_attributes)
		);

		return $script_tag_attributes;
	}

	/**
	 * @return string
	 */
	public function getCurrentRoute() {
		$route = '';
		if (isset($this->request->get['route'])) {
			$route = (string)$this->request->get['route'];
		}
		$this->event->trigger('newsmanremarketing/remarketing_get_current_route/after', array(&$route));

		return $route;
	}

	/**
	 * Event handler for catalog/view/common/cart/after.
	 *
	 * Appends a JSON payload of the current cart products to the rendered minicart
	 * HTML so that the minicart-DOM-based tracker (minicart.twig) can read it
	 * without parsing theme markup.
	 *
	 * Skipped when Theme Cart Compatibility is enabled (cart.twig handles tracking
	 * via XHR/fetch interception in that mode).
	 *
	 * @param string $route
	 * @param array  $data
	 * @param string $output
	 *
	 * @return void
	 */
	public function eventViewCommonCartAfter(&$route, &$data, &$output) {
		if (!$this->nzmconfig->isRemarketingActive()) {
			return;
		}
		if ($this->nzmconfig->isThemeCartCompatibility()) {
			return;
		}

		$products = array();
		try {
			$cart_products = $this->cart->getProducts();
		} catch (\Exception $e) {
			return;
		}

		$show_price = ($this->customer->isLogged() || !$this->config->get('config_customer_price'));

		foreach ($cart_products as $product) {
			$price = 0.0;
			if ($show_price && isset($product['price'], $product['tax_class_id'])) {
				$unit_price = $this->tax->calculate(
					$product['price'],
					$product['tax_class_id'],
					$this->config->get('config_tax')
				);
				$price = (float)$unit_price;
			}

			$products[] = array(
				'id'       => isset($product['product_id']) ? (string)$product['product_id'] : '',
				'name'     => isset($product['name']) ? (string)$product['name'] : '',
				'price'    => $price,
				'quantity' => isset($product['quantity']) ? (int)$product['quantity'] : 0,
			);
		}

		$json = json_encode(
			$products,
			JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
		if ($json === false) {
			return;
		}

		// Inject the JSON inside the minicart's <ul> as a hidden <li>. This makes
		// it survive the OpenCart 3 default theme's selective refresh, which uses
		// $('#cart > ul').load('...common/cart/info ul li') — only <li> elements
		// inside a <ul> are kept. Insert before the last </ul> in the rendered view.
		$tag = '<li class="newsman-cart-data" style="display:none">'
			. '<script type="application/json" data-newsman-cart>' . $json . '</script>'
			. '</li>';

		$pos = strrpos($output, '</ul>');
		if ($pos === false) {
			return;
		}
		$output = substr_replace($output, $tag, $pos, 0);
	}
}
