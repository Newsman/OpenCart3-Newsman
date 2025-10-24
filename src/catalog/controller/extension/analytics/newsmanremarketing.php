<?php

class ControllerExtensionAnalyticsNewsmanremarketing extends Controller
{
	/**
	 * Returnează doar ID-ul celei mai specifice categorii
	 * (funcția stabilită anterior, nemodificată)
	 */
	private function getDeepestCategoryId()
	{
		// 1) Din URL path=...
		if (!empty($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
			return (int)array_pop($parts);
		}

		// 2) Ne aflăm pe product page?
		if (!empty($this->request->get['product_id'])) {
			$this->load->model('catalog/product');
			$cats = $this->model_catalog_product->getCategories((int)$this->request->get['product_id']);

			if ($cats) {
				$deepestId = 0;
				$deepestLevel = -1;
				$depthCache = [];

				foreach ($cats as $c) {
					$cid = (int)$c['category_id'];

					// întâi încercăm category_path
					$level = $this->getCategoryDepthFromPathTable($cid);

					// fallback dacă level = 0
					if ($level <= 0) {
						if (isset($depthCache[$cid])) {
							$level = $depthCache[$cid];
						} else {
							$level = $this->getCategoryDepthByParent($cid);
							$depthCache[$cid] = $level;
						}
					}

					if ($level > $deepestLevel) {
						$deepestLevel = $level;
						$deepestId = $cid;
					}
				}

				// ultim fallback: toate au level=0 → returnează id-ul cel mai mare
				if ($deepestLevel <= 0) {
					$maxId = 0;
					foreach ($cats as $c) {
						$maxId = max($maxId, (int)$c['category_id']);
					}
					return $maxId;
				}

				return $deepestId;
			}
		}

		// 3) alt context (home etc.)
		return 0;
	}

	private function getCategoryDepthFromPathTable($category_id)
	{
		$query = $this->db->query("SELECT MAX(level) AS depth 
                               FROM " . DB_PREFIX . "category_path 
                               WHERE category_id = '" . (int)$category_id . "'");
		return isset($query->row['depth']) ? (int)$query->row['depth'] : 0;
	}

	private function getCategoryDepthByParent($category_id)
	{
		$level = 0;
		$visited = [];
		$current = (int)$category_id;

		while ($current && !in_array($current, $visited)) {
			$visited[] = $current;
			$query = $this->db->query("SELECT parent_id 
                                   FROM " . DB_PREFIX . "category 
                                   WHERE category_id = '" . (int)$current . "' LIMIT 1");

			if (empty($query->row)) break;

			$parent = (int)$query->row['parent_id'];
			if ($parent && $parent != $current) {
				$level++;
				$current = $parent;
			} else {
				break;
			}
		}
		return $level;
	}

	/**
	 * ✅ Nou — returnează numele categoriei după ID.
	 */
	private function getCategoryName($category_id, $language_id = null)
	{
		if (!$category_id) return '';

		if ($language_id === null) {
			$language_id = (int)$this->config->get('config_language_id');
		}

		$query = $this->db->query("SELECT name 
                               FROM " . DB_PREFIX . "category_description 
                               WHERE category_id = '" . (int)$category_id . "'
                                 AND language_id = '" . (int)$language_id . "'
                               LIMIT 1");

		return isset($query->row['name']) ? $query->row['name'] : '';
	}



	//de aici vine clasa standard cu category, care era si inainte, de lua doar o categorie
	protected function getCategoryPath($category_id)
	{
		$path = '';
		$category = $this->model_catalog_category->getCategory($category_id);

		if (!array_key_exists('name', $category)) {
			return '';
		}

		if ($category['parent_id'] != 0) {
			$path .= $this->getCategoryPath($category['parent_id']) . ' / ';
		}

		$path .= $category['name'];
		return $path;
	}

	// Maps Opencart product data to Google Analytics product structure
	protected function getProduct($order_id, $product)
	{
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('checkout/order');

		$oc_product = $this->model_catalog_product->getProduct($product["product_id"]);

		// get product options
		$product["variant"] = '';
		$variants = $this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]);
		foreach ($variants as $variant)
			$product["variant"] = $variant["value"] . " | ";
		if ($product["variant"]) {
			$product["variant"] = substr($product["variant"], 0, -3);
		}

		// get category path
		$oc_categories = $this->model_catalog_product->getCategories($product["product_id"]);
		$oc_category = [];
		if (sizeof($oc_categories) > 0) {
			$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
			if (sizeof($oc_category) > 0) {
				$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
			} else {
				$oc_category["path"] = '';
			}
		}


		// $this->log->write(print_r($this->model_checkout_order->getOrderOptions($order_id, $product["order_product_id"]), TRUE));

		$ga_product = [
			"id" => $product["product_id"],
			"name" => $product["name"],
			"SKU" => $oc_product["sku"],
			"brand" => $oc_product["manufacturer"],
			"category" => $oc_category["path"],
			"variant" => $product["variant"],
			"quantity" => $product["quantity"],
			"price" => $product["price"]
		];
		return $ga_product;
	}


	protected function getShipping($totals)
	{
		$shipping = 0.00;
		foreach ($totals as $total) {
			if ($total["code"] == 'shipping') {
				$shipping += $total["value"];
			}
		}
		return $shipping;
	}


	protected function getTax($totals)
	{
		$tax = 0.00;
		foreach ($totals as $total) {
			if ($total["code"] == 'tax') {
				$tax += $total["value"];
			}
		}
		return $tax;
	}


	public function index()
	{
		$this->load->model('checkout/order');

		$endpoint = "https://retargeting.newsmanapp.com/js/retargeting/track.js";
		$endpointHost = "https://retargeting.newsmanapp.com";

		$tag = "";

		// get Route
		$route = '';
		if (isset($this->request->get['route'])) {
			$route = (string)$this->request->get['route'];
		}

		// get Tracking ID
		$tracking_id = $this->config->get('analytics_newsmanremarketing_trackingid');

		$_domain = $_SERVER['SERVER_NAME'];

		// If not Purchase
		if ($route != 'checkout/success') {
			$tag .= <<<TAG
				<script>

				//Newsman remarketing tracking code REPLACEABLE

				var remarketingid = '$tracking_id';
				var _nzmPluginInfo = '1.3:opencart3';

				//Newsman remarketing tracking code REPLACEABLE

				//Newsman remarketing tracking code

var endpoint = 'https://retargeting.newsmanapp.com';
var remarketingEndpoint = endpoint + '/js/retargeting/track.js';

var _nzm = _nzm || [];
var _nzm_config = _nzm_config || [];
_nzm_config['disable_datalayer'] = 1;
_nzm_tracking_server = endpoint;
(function() {
    var a, methods, i;
    a = function(f) {
        return function() {
            _nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));
        }
    };
    methods = ['identify', 'track', 'run'];
    for (i = 0; i < methods.length; i++) {
        _nzm[methods[i]] = a(methods[i])
    };
    s = document.getElementsByTagName('script')[0];
    var script_dom = document.createElement('script');
    script_dom.async = true;
    script_dom.id = 'nzm-tracker';
    script_dom.setAttribute('data-site-id', remarketingid);
    script_dom.src = remarketingEndpoint;

    if (_nzmPluginInfo.indexOf('shopify') !== -1) {
        script_dom.onload = function(){
            if (typeof newsmanRemarketingLoad === 'function')
                newsmanRemarketingLoad();
        }
    }
    s.parentNode.insertBefore(script_dom, s);
})();
_nzm.run('require', 'ec');

//Newsman remarketing tracking code

//Newsman remarketing auto events REPLACEABLE

var ajaxurl = '/index.php?route=extension/module/newsman&newsman=getCart.json';

//Newsman remarketing auto events REPLACEABLE

//Newsman remarketing auto events

		var isProd = true;
		let lastCart = sessionStorage.getItem('lastCart');
		if (lastCart === null)
			lastCart = {};
		var lastCartFlag = false;
		var firstLoad = true;
		var bufferedXHR = false;
		var unlockClearCart = true;
		var isError = false;
		let secondsAllow = 5;
		let msRunAutoEvents = 5000;
		let msClick = new Date();
		var documentComparer = document.location.hostname;
		var documentUrl = document.URL;
		var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);
		let startTime, endTime;
		function startTimePassed() {
			startTime = new Date();
		}
		;startTimePassed();
		function endTimePassed() {
			var flag = false;
			endTime = new Date();
			var timeDiff = endTime - startTime;
			timeDiff /= 1000;
			var seconds = Math.round(timeDiff);
			if (firstLoad)
				flag = true;
			if (seconds >= secondsAllow)
				flag = true;
			return flag;
		}
		if (sameOrigin) {
			NewsmanAutoEvents();
			setInterval(NewsmanAutoEvents, msRunAutoEvents);
			detectClicks();
			detectXHR();
		}
		function timestampGenerator(min, max) {
			min = Math.ceil(min);
			max = Math.floor(max);
			return Math.floor(Math.random() * (max - min + 1)) + min;
		}
		function NewsmanAutoEvents() {
			if (!endTimePassed()) {
				if (!isProd)
					console.log('newsman remarketing: execution stopped at the beginning, ' + secondsAllow + ' seconds didn\"t pass between requests');
				return;
			}
			if (isError && isProd == true) {
				console.log('newsman remarketing: an error occurred, set isProd = false in console, script execution stopped;');
				return;
			}
			let xhr = new XMLHttpRequest()
			if (bufferedXHR || firstLoad) {
				var paramChar = '?t=';
				if (ajaxurl.indexOf('?') >= 0)
					paramChar = '&t=';
				var timestamp = paramChar + Date.now() + timestampGenerator(999, 999999999);
				try {
					xhr.open('GET', ajaxurl + timestamp, true);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: malformed XHR url');
					isError = true;
				}
				startTimePassed();
				xhr.onload = function() {
					if (xhr.status == 200 || xhr.status == 201) {
						try {
							var response = JSON.parse(xhr.responseText);
						} catch (error) {
							if (!isProd)
								console.log('newsman remarketing: error occured json parsing response');
							isError = true;
							return;
						}
						//check for engine name
						//if shopify
						if (_nzmPluginInfo.indexOf('shopify') !== -1) {
							if (!isProd)
								console.log('newsman remarketing: shopify detected, products will be pushed with custom props');
							var products = [];
							if (response.item_count > 0) {
								response.items.forEach(function(item) {
									products.push({
										'id': item.id,
										'name': item.product_title,
										'quantity': item.quantity,
										'price': parseFloat(item.price)
									});
								});
							}
							response = products;
						}
						lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
						if (lastCart === null) {
							lastCart = {};
							if (!isProd)
								console.log('newsman remarketing: lastCart === null');
						}
						//check cache
						if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
							var objComparer = response;
							var missingProp = false;
							lastCart.forEach(e=>{
								if (!e.hasOwnProperty('name')) {
									missingProp = true;
								}
							}
							);
							if (missingProp)
								objComparer.forEach(function(v) {
									delete v.name
								});
							if (JSON.stringify(lastCart) === JSON.stringify(objComparer)) {
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is unchanged');
								lastCartFlag = true;
							} else {
								lastCartFlag = false;
								if (!isProd)
									console.log('newsman remarketing: cache loaded, cart is changed');
							}
						}
						if (response.length > 0 && lastCartFlag == false) {
							nzmAddToCart(response);
						}//send only when on last request, products existed
						else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {
							nzmClearCart();
							if (!isProd)
								console.log('newsman remarketing: clear cart sent');
						} else {
							if (!isProd)
								console.log('newsman remarketing: request not sent');
						}
						firstLoad = false;
						bufferedXHR = false;
					} else {
						if (!isProd)
							console.log('newsman remarketing: response http status code is not 200');
						isError = true;
					}
				}
				try {
					xhr.send(null);
				} catch (ex) {
					if (!isProd)
						console.log('newsman remarketing: error on xhr send');
					isError = true;
				}
			} else {
				if (!isProd)
					console.log('newsman remarketing: !buffered xhr || first load');
			}
		}
		function nzmClearCart() {
			_nzm.run('ec:setAction', 'clear_cart');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
			sessionStorage.setItem('lastCart', JSON.stringify([]));
			unlockClearCart = false;
		}
		function nzmAddToCart(response) {
			_nzm.run('ec:setAction', 'clear_cart');
			if (!isProd)
				console.log('newsman remarketing: clear cart sent, add to cart function');
			detailviewEvent(response);
		}
		function detailviewEvent(response) {
			if (!isProd)
				console.log('newsman remarketing: detailviewEvent execute');
			_nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, function() {
				if (!isProd)
					console.log('newsman remarketing: executing add to cart callback');
				var products = [];
				for (var item in response) {
					if (response[item].hasOwnProperty('id')) {
						_nzm.run('ec:addProduct', response[item]);
						products.push(response[item]);
					}
				}
				_nzm.run('ec:setAction', 'add');
				_nzm.run('send', 'event', 'UX', 'click', 'add to cart');
				sessionStorage.setItem('lastCart', JSON.stringify(products));
				unlockClearCart = true;
				if (!isProd)
					console.log('newsman remarketing: cart sent');
			});
		}
		function detectClicks() {
			window.addEventListener('click', function(event) {
				msClick = new Date();
			}, false);
		}
		function detectXHR() {
			var proxied = window.XMLHttpRequest.prototype.send;
			window.XMLHttpRequest.prototype.send = function() {
				var pointer = this;
				var validate = false;
				var timeValidate = false;
				var intervalId = window.setInterval(function() {
					if (pointer.readyState != 4) {
						return;
					}
					var msClickPassed = new Date();
					var timeDiff = msClickPassed.getTime() - msClick.getTime();
					if (timeDiff > 5000) {
						validate = false;
					} else {
						timeValidate = true;
					}
					var _location = pointer.responseURL;
					//own request exclusion
					if (timeValidate) {
						if (_location.indexOf('getCart.json') >= 0 || //magento 2.x
						_location.indexOf('/static/') >= 0 || _location.indexOf('/pub/static') >= 0 || _location.indexOf('/customer/section') >= 0 || //opencart 1
						_location.indexOf('getCart=true') >= 0 || //shopify
						_location.indexOf('cart.js') >= 0) {
							validate = false;
						} else {
							//check for engine name
							if (_nzmPluginInfo.indexOf('shopify') !== -1) {
								validate = true;
							} else {
								if (_location.indexOf(window.location.origin) !== -1)
									validate = true;
							}
						}
						if (validate) {
							bufferedXHR = true;
							if (!isProd)
								console.log('newsman remarketing: ajax request fired and catched from same domain, NewsmanAutoEvents called');
							NewsmanAutoEvents();
						}
					}
					clearInterval(intervalId);
				}, 1);
				return proxied.apply(this, [].slice.call(arguments));
			}
			;
		}

		//Newsman remarketing auto events

				</script>
TAG;

			switch ($route) {
				case "product/product":
					$this->load->model('catalog/product');
					$this->load->model('catalog/category');
					$this->load->model('checkout/order');

					$id = $this->request->get['product_id'];

					$oc_product = $this->model_catalog_product->getProduct($id);
					$oc_categories = $this->model_catalog_product->getCategories($id);
					$oc_category = [];
					if (sizeof($oc_categories) > 0) {
						$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
						if (sizeof($oc_category) > 0) {
							$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
						} else {
							$oc_category["path"] = '';
						}
					}

					if ($oc_product && is_array($oc_product)) {
						$tag .= "
			<script>
			_nzm.run('ec:addProduct', {
			    'id': '" . (isset($oc_product['product_id']) ? $oc_product['product_id'] : '') . "',
			    'name': '" . (isset($oc_product['name']) ? $oc_product['name'] : '') . "',
			    'category': '" . $this->getCategoryName($this->getDeepestCategoryId()) . "',
			    'price': '" . (isset($oc_product['price']) ? $oc_product['price'] : '') . "',
			    'list': '" . (isset($list) ? $list : 'Product Page') . "'
			});
			_nzm.run('ec:setAction', 'detail');
			</script>
			";
					}
					break;

				case "checkout/cart":

					$tag .= "
					<script>

					</script>
					";

					break;

				case "checkout/checkout":
					$this->load->model('catalog/product');
					$this->load->model('catalog/category');
					$this->load->model('checkout/order');

					$products = $this->cart->getProducts();

					foreach ($products as $item) {
						$oc_categories = $this->model_catalog_product->getCategories($item["product_id"]);
						$oc_category = [];
						if (sizeof($oc_categories) > 0) {
							$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
							if (sizeof($oc_category) > 0) {
								$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
							} else {
								$oc_category["path"] = '';
							}
						}

						$tag .= "
					<script></script>";
					}

					$tag .= "<script></script>";
					break;

				case "product/category":
					$this->load->model('catalog/product');
					$this->load->model('catalog/category');

					//Obsolete
					/*$prod = (!empty($this->session->data['ga_orderDetails'])) ? $this->session->data['ga_orderDetails'] : array();*/

					$prod = array();

					$tag .= "";

					$pos = 1;

					foreach ($prod as $item) {
						$oc_categories = $this->model_catalog_product->getCategories($item["product_id"]);
						$oc_category = [];
						if (sizeof($oc_categories) > 0) {
							$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
							if (sizeof($oc_category) > 0) {
								$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
							} else {
								$oc_category["path"] = '';
							}
						}

						$tag .= "
					<script>
 _nzm.run('ec:addImpression', {
                    'id': " . $item['product_id'] . ",
                    'name': '" . $item['name'] . "',
                    'category': '" .  $this->getCategoryName($this->getDeepestCategoryId()) . "',
					price: " . filter_var($item['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) . ",
                    list: 'Category Page',
                    position: '" . $pos . "'
                    });
					</script>";

						$pos++;
					}

					break;
			}

			$tag .= <<<TAG

<script>
_nzm.run('send', 'pageview');
</script>

TAG;

			return $tag;
		} // Purchase
		else {
			$purchase_event = null;
			$products_event = null;

			if (isset($this->session->data['ga_orderDetails'])) {
				$orderDetails = $this->session->data['ga_orderDetails'];

				$e = $orderDetails["email"];
				$f = $orderDetails["firstname"];
				$l = $orderDetails["lastname"];

				$order_id = (!empty($orderDetails["order_id"])) ? $orderDetails["order_id"] : null;
				$order_totals = $this->model_checkout_order->getOrderTotals($order_id);
				// $this->log->write(print_r($order_totals, TRUE));

				$ob_products = [];
				if (isset($this->session->data['ga_orderProducts'])) {
					foreach ($this->session->data['ga_orderProducts'] as $product)
						array_push($ob_products, $this->getProduct($order_id, $product));
				}

				foreach ($ob_products as $item) {
					$products_event .=
						"_nzm.run( 'ec:addProduct', {" .
						"'id': '" . $item["id"] . "'," .
						"'name': '" . $item["name"] . "'," .
						"'category': '" .  $this->getCategoryName($this->getDeepestCategoryId()) . "'," .
						"'price': '" . $item["price"] . "'," .
						"'quantity': '" . $item["quantity"] . "'," .
						"} );";
				}

				$ob_order = [];

				if (!empty($order_id)) {
					$ob_order = [
						"id" => $order_id,
						"affiliation" => $orderDetails["store_name"],
						"revenue" => $orderDetails["total"],
						"tax" => 0,
						"shipping" => 0
					];
				}

				$purchase_event = json_encode($ob_order);
			}

			$tag = <<<TAG

					<script>

					//Newsman remarketing tracking code

					var endpoint = 'https://retargeting.newsmanapp.com';
					var remarketingEndpoint = endpoint + '/js/retargeting/track.js';
					var remarketingid = '$tracking_id';
					var ajaxurl = '/index.php?route=extension/module/newsman&newsman=getCart.json';

					var _nzmPluginInfo = '1.2:opencart3';
					var _nzm = _nzm || [];
					var _nzm_config = _nzm_config || [];
					_nzm_config['disable_datalayer'] = 1;
					_nzm_tracking_server = endpoint;
					(function() {
						var a, methods, i;
						a = function(f) {
							return function() {
								_nzm.push([f].concat(Array.prototype.slice.call(arguments, 0)));
							}
						};
						methods = ['identify', 'track', 'run'];
						for (i = 0; i < methods.length; i++) {
							_nzm[methods[i]] = a(methods[i])
						};
						s = document.getElementsByTagName('script')[0];
						var script_dom = document.createElement('script');
						script_dom.async = true;
						script_dom.id = 'nzm-tracker';
						script_dom.setAttribute('data-site-id', remarketingid);
						script_dom.src = remarketingEndpoint;
						s.parentNode.insertBefore(script_dom, s);
					})();
					_nzm.run('require', 'ec');

					//Newsman remarketing tracking code

					//Newsman remarketing auto events

					var isProd = true;
					let lastCart = sessionStorage.getItem('lastCart');
					if (lastCart === null)
						lastCart = {};
					var lastCartFlag = false;
					var firstLoad = true;
					var bufferedXHR = false;
					var unlockClearCart = true;
					var isError = false;
					let secondsAllow = 5;
					let msRunAutoEvents = 5000;
					let msClick = new Date();
					var documentComparer = document.location.hostname;
					var documentUrl = document.URL;
					var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);
					let startTime, endTime;
					function startTimePassed() {
						startTime = new Date();
					}
					;startTimePassed();
					function endTimePassed() {
						var flag = false;
						endTime = new Date();
						var timeDiff = endTime - startTime;
						timeDiff /= 1000;
						var seconds = Math.round(timeDiff);
						if (firstLoad)
							flag = true;
						if (seconds >= secondsAllow)
							flag = true;
						return flag;
					}
					if (sameOrigin) {
						NewsmanAutoEvents();
						setInterval(NewsmanAutoEvents, msRunAutoEvents);
						detectClicks();
						detectXHR();
					}
					function timestampGenerator(min, max) {
						min = Math.ceil(min);
						max = Math.floor(max);
						return Math.floor(Math.random() * (max - min + 1)) + min;
					}
					function NewsmanAutoEvents() {
						if (!endTimePassed()) {
							if (!isProd)
								console.log('newsman remarketing: execution stopped at the beginning, ' + secondsAllow + ' seconds didn\"t pass between requests');
							return;
						}
						if (isError && isProd == true) {
							console.log('newsman remarketing: an error occurred, set isProd = false in console, script execution stopped;');
							return;
						}
						let xhr = new XMLHttpRequest()
						if (bufferedXHR || firstLoad) {
							var paramChar = '?t=';
							if (ajaxurl.indexOf('?') >= 0)
								paramChar = '&t=';
							var timestamp = paramChar + Date.now() + timestampGenerator(999, 999999999);
							try {
								xhr.open('GET', ajaxurl + timestamp, true);
							} catch (ex) {
								if (!isProd)
									console.log('newsman remarketing: malformed XHR url');
								isError = true;
							}
							startTimePassed();
							xhr.onload = function() {
								if (xhr.status == 200 || xhr.status == 201) {
									try {
										var response = JSON.parse(xhr.responseText);
									} catch (error) {
										if (!isProd)
											console.log('newsman remarketing: error occured json parsing response');
										isError = true;
										return;
									}
									//check for engine name
									//if shopify
									if (_nzmPluginInfo.indexOf('shopify') !== -1) {
										if (!isProd)
											console.log('newsman remarketing: shopify detected, products will be pushed with custom props');
										var products = [];
										if (response.item_count > 0) {
											response.items.forEach(function(item) {
												products.push({
													'id': item.id,
													'name': item.product_title,
													'quantity': item.quantity,
													'price': parseFloat(item.price)
												});
											});
										}
										response = products;
									}
									lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
									if (lastCart === null) {
										lastCart = {};
										if (!isProd)
											console.log('newsman remarketing: lastCart === null');
									}
									//check cache
									if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
										var objComparer = response;
										var missingProp = false;
										lastCart.forEach(e=>{
											if (!e.hasOwnProperty('name')) {
												missingProp = true;
											}
										}
										);
										if (missingProp)
											objComparer.forEach(function(v) {
												delete v.name
											});
										if (JSON.stringify(lastCart) === JSON.stringify(objComparer)) {
											if (!isProd)
												console.log('newsman remarketing: cache loaded, cart is unchanged');
											lastCartFlag = true;
										} else {
											lastCartFlag = false;
											if (!isProd)
												console.log('newsman remarketing: cache loaded, cart is changed');
										}
									}
									if (response.length > 0 && lastCartFlag == false) {
										nzmAddToCart(response);
									}//send only when on last request, products existed
									else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {
										nzmClearCart();
										if (!isProd)
											console.log('newsman remarketing: clear cart sent');
									} else {
										if (!isProd)
											console.log('newsman remarketing: request not sent');
									}
									firstLoad = false;
									bufferedXHR = false;
								} else {
									if (!isProd)
										console.log('newsman remarketing: response http status code is not 200');
									isError = true;
								}
							}
							try {
								xhr.send(null);
							} catch (ex) {
								if (!isProd)
									console.log('newsman remarketing: error on xhr send');
								isError = true;
							}
						} else {
							if (!isProd)
								console.log('newsman remarketing: !buffered xhr || first load');
						}
					}
					function nzmClearCart() {
						_nzm.run('ec:setAction', 'clear_cart');
						_nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
						sessionStorage.setItem('lastCart', JSON.stringify([]));
						unlockClearCart = false;
					}
					function nzmAddToCart(response) {
						_nzm.run('ec:setAction', 'clear_cart');
						if (!isProd)
							console.log('newsman remarketing: clear cart sent, add to cart function');
						detailviewEvent(response);
					}
					function detailviewEvent(response) {
						if (!isProd)
							console.log('newsman remarketing: detailviewEvent execute');
						_nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, function() {
							if (!isProd)
								console.log('newsman remarketing: executing add to cart callback');
							var products = [];
							for (var item in response) {
								if (response[item].hasOwnProperty('id')) {
									_nzm.run('ec:addProduct', response[item]);
									products.push(response[item]);
								}
							}
							_nzm.run('ec:setAction', 'add');
							_nzm.run('send', 'event', 'UX', 'click', 'add to cart');
							sessionStorage.setItem('lastCart', JSON.stringify(products));
							unlockClearCart = true;
							if (!isProd)
								console.log('newsman remarketing: cart sent');
						});
					}
					function detectClicks() {
						window.addEventListener('click', function(event) {
							msClick = new Date();
						}, false);
					}
					function detectXHR() {
						var proxied = window.XMLHttpRequest.prototype.send;
						window.XMLHttpRequest.prototype.send = function() {
							var pointer = this;
							var validate = false;
							var timeValidate = false;
							var intervalId = window.setInterval(function() {
								if (pointer.readyState != 4) {
									return;
								}
								var msClickPassed = new Date();
								var timeDiff = msClickPassed.getTime() - msClick.getTime();
								if (timeDiff > 1000) {
									validate = false;
								} else {
									timeValidate = true;
								}
								var _location = pointer.responseURL;
								//own request exclusion
								if (timeValidate) {
									if (_location.indexOf('getCart.json') >= 0 || //magento 2.x
									_location.indexOf('/static/') >= 0 || _location.indexOf('/pub/static') >= 0 || _location.indexOf('/customer/section') >= 0 || //opencart 1
									_location.indexOf('getCart=true') >= 0 || //shopify
									_location.indexOf('cart.js') >= 0) {
										validate = false;
									} else {
										//check for engine name
										if (_nzmPluginInfo.indexOf('shopify') !== -1) {
											validate = true;
										} else {
											if (_location.indexOf(window.location.origin) !== -1)
												validate = true;
										}
									}
									if (validate) {
										bufferedXHR = true;
										if (!isProd)
											console.log('newsman remarketing: ajax request fired and catched from same domain, NewsmanAutoEvents called');
										NewsmanAutoEvents();
									}
								}
								clearInterval(intervalId);
							}, 1);
							return proxied.apply(this, [].slice.call(arguments));
						}
						;
					}

					//Newsman remarketing auto events

TAG;

			$tag .= <<<TAG

			setTimeout(function(){

				$products_event
				_nzm.run('ec:setAction', 'purchase', $purchase_event);
				_nzm.run('send', 'pageview');

			}, 1000);

			</script>

TAG;

			unset($this->session->data['ga_orderDetails']);
			unset($this->session->data['ga_orderProducts']);

			return $tag;
		}
	}
}
