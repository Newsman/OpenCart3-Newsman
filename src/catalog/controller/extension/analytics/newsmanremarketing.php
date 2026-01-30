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
		$data['nzm_run'] = $track->escapeHtml($this->nzmconfig->getJsTrackRunFunc());
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

		$this->event->trigger('newsmanremarketing/script_cart_render/before', array(&$data));
		$output = $this->load->view('extension/analytics/newsman/cart', $data);
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
}
