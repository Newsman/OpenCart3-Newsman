<?php

class ControllerExtensionAnalyticsNewsmanremarketing extends Controller
{
	protected function getCategoryPath($category_id)
	{
		$path = '';
		$category = $this->model_catalog_category->getCategory($category_id);

		if ($category['parent_id'] != 0)
		{
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
		if ($product["variant"])
		{
			$product["variant"] = substr($product["variant"], 0, -3);
		}

		// get category path	
		$oc_categories = $this->model_catalog_product->getCategories($product["product_id"]);
		$oc_category = [];
		if (sizeof($oc_categories) > 0)
		{
			$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
			if (sizeof($oc_category) > 0)
			{
				$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
			} else
			{
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
		foreach ($totals as $total)
		{
			if ($total["code"] == 'shipping')
			{
				$shipping += $total["value"];
			}
		}
		return $shipping;
	}


	protected function getTax($totals)
	{
		$tax = 0.00;
		foreach ($totals as $total)
		{
			if ($total["code"] == 'tax')
			{
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
		if (isset($this->request->get['route']))
		{
			$route = (string)$this->request->get['route'];
		}

		// get Tracking ID
		$tracking_id = $this->config->get('analytics_newsmanremarketing_trackingid');

		$_domain = $_SERVER['SERVER_NAME'];

		// If not Purchase
		if ($route != 'checkout/success')
		{
			$tag .= <<<TAG
				<script>

				//Newsman remarketing tracking code  

				var endpoint = 'https://retargeting.newsmanapp.com';
				var remarketingEndpoint = endpoint + '/js/retargeting/track.js';
				var remarketingid = '$tracking_id';
				
				var _nzmPluginInfo = '1.1:opencart3';
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
				var ajaxurl = 'https://' + document.location.hostname + '/index.php?route=extension/module/newsman&newsman=getCart.json';
				var documentComparer = '$_domain';
				var documentUrl = document.URL;
				var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);
				
				let startTime, endTime;
				
				function startTimePassed() {
					startTime = new Date();
				};
				
				startTimePassed();
				
				function endTimePassed() {
					var flag = false;
				
					endTime = new Date();
					var timeDiff = endTime - startTime;
				
					timeDiff /= 1000;
				
					var seconds = Math.round(timeDiff);
				
					if (firstLoad)
						flag = true;
				
					if (seconds >= 5)
						flag = true;
				
					return flag;
				}
				
				if (sameOrigin) {
					NewsmanAutoEvents();
					setInterval(NewsmanAutoEvents, 5000);
				
					detectXHR();
				}

				function timestamp(min, max) {
					min = Math.ceil(min);
					max = Math.floor(max);
					return Math.floor(Math.random() * (max - min + 1)) + min;
				}
				
				function NewsmanAutoEvents() {
				
					if (!endTimePassed())
						return;
				
					let xhr = new XMLHttpRequest()

					if (bufferedXHR || firstLoad) {	
						
						var timestamp = "?t=" + Date.now() + timestamp();

						xhr.open('GET', ajaxurl + timestamp, true);
				
						startTimePassed();
				
						xhr.onload = function() {
				
							if (xhr.status == 200 || xhr.status == 201) {
				
								var response = JSON.parse(xhr.responseText);
				
								lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
				
								if (lastCart === null)
									lastCart = {};
				
								//check cache
								if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
									if (JSON.stringify(lastCart) === JSON.stringify(response)) {
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
				
									addToCart(response);
				
								}
								//send only when on last request, products existed
								else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {
				
									clearCart();
				
									if (!isProd)
										console.log('newsman remarketing: clear cart sent');
				
								} else {
				
									if (!isProd)
										console.log('newsman remarketing: request not sent');
				
								}
				
								firstLoad = false;
								bufferedXHR = false;
				
							}
				
						}
				
						xhr.send(null);
				
					} else {
						if (!isProd)
							console.log('newsman remarketing: !buffered xhr || first load');
					}
				
				}
				
				function clearCart() {
				
					_nzm.run('ec:setAction', 'clear_cart');
					_nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
				
					sessionStorage.setItem('lastCart', JSON.stringify([]));
				
					unlockClearCart = false;
				
				}
				
				function addToCart(response) {
				
					_nzm.run('ec:setAction', 'clear_cart');
					_nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, _nzm.createFunctionWithTimeout(function() {
				
						for (var item in response) {
				
							_nzm.run('ec:addProduct',
								response[item]
							);
				
						}
				
						_nzm.run('ec:setAction', 'add');
						_nzm.run('send', 'event', 'UX', 'click', 'add to cart');
				
						sessionStorage.setItem('lastCart', JSON.stringify(response));
						unlockClearCart = true;
				
						if (!isProd)
							console.log('newsman remarketing: cart sent');
				
					}));
				
				}
				
				function detectXHR() {
				
					var proxied = window.XMLHttpRequest.prototype.send;
					window.XMLHttpRequest.prototype.send = function() {
				
						var pointer = this;
						var validate = false;
						var intervalId = window.setInterval(function() {
				
							if (pointer.readyState != 4) {
								return;
							}
				
							var _location = pointer.responseURL;
				
							//own request exclusion
							if (
								pointer.responseURL.indexOf('getCart.json') >= 0 ||
								//magento
								pointer.responseURL.indexOf('/static/') >= 0 ||
								pointer.responseURL.indexOf('/pub/static') >= 0 ||
								pointer.responseURL.indexOf('/customer/section') >= 0
							) {
								validate = false;
							} else {
								if (_location.indexOf(window.location.origin) !== -1)
									validate = true;
							}
				
							if (validate) {
								bufferedXHR = true;
				
								if (!isProd)
									console.log('newsman remarketing: ajax request fired and catched from same domain');
				
								NewsmanAutoEvents();
							}
				
							clearInterval(intervalId);
				
						}, 1);
				
						try{
						return proxied.apply(this, [].slice.call(arguments));
						}
						catch (error){
							if (!isProd)
							{
								console.log('newsman remarketing: error');
								console.log(error);
							}
						}
					};
				
				}
				
				//Newsman remarketing auto events

				</script>
TAG;

			switch ($route)
			{
				case "product/product":
					$this->load->model('catalog/product');
					$this->load->model('catalog/category');
					$this->load->model('checkout/order');

					$id = $this->request->get['product_id'];

					$oc_product = $this->model_catalog_product->getProduct($id);
					$oc_categories = $this->model_catalog_product->getCategories($id);
					$oc_category = [];
					if (sizeof($oc_categories) > 0)
					{
						$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
						if (sizeof($oc_category) > 0)
						{
							$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
						} else
						{
							$oc_category["path"] = '';
						}
					}

					$tag .= "
					<script>
 					_nzm.run('ec:addProduct', {
                    'id': " . $oc_product['product_id'] . ",
                    'name': '" . $oc_product['name'] . "',
                    'category': '" . $oc_category['path'] . "',
                    price: " . $oc_product['price'] . ",
                    list: 'Product Page'});_nzm.run('ec:setAction', 'detail');

                 </script>
                 ";
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

					foreach ($products as $item)
					{
						$oc_categories = $this->model_catalog_product->getCategories($item["product_id"]);
						$oc_category = [];
						if (sizeof($oc_categories) > 0)
						{
							$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
							if (sizeof($oc_category) > 0)
							{
								$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
							} else
							{
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

					$prod = (!empty($this->session->data['ga_orderDetails'])) ? $this->session->data['ga_orderDetails'] : array();

					$tag .= "";

					$pos = 1;

					foreach ($prod as $item)
					{
						$oc_categories = $this->model_catalog_product->getCategories($item["product_id"]);
						$oc_category = [];
						if (sizeof($oc_categories) > 0)
						{
							$oc_category = $this->model_catalog_category->getCategory($oc_categories[0]["category_id"]);
							if (sizeof($oc_category) > 0)
							{
								$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
							} else
							{
								$oc_category["path"] = '';
							}
						}

						$tag .= "
					<script>
 _nzm.run('ec:addImpression', {
                    'id': " . $item['product_id'] . ",
                    'name': '" . $item['name'] . "',
                    'category': '" . $oc_category['path'] . "',
                    price: " . substr($item['price'], 1, strlen($item['price'])) . ",
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
		else
		{
			$purchase_event = null;	
			$products_event = null;

			if (isset($this->session->data['ga_orderDetails']))
			{
				$orderDetails = $this->session->data['ga_orderDetails'];
			
				$e = $orderDetails["email"];
				$f = $orderDetails["firstname"];
				$l = $orderDetails["lastname"];
				
				$order_id = (!empty($orderDetails["order_id"])) ? $orderDetails["order_id"] : null;
				$order_totals = $this->model_checkout_order->getOrderTotals($order_id);
				// $this->log->write(print_r($order_totals, TRUE));

				$ob_products = [];
				if (isset($this->session->data['ga_orderProducts']))
				{
					foreach ($this->session->data['ga_orderProducts'] as $product)
						array_push($ob_products, $this->getProduct($order_id, $product));
				}

				foreach($ob_products as $item){
					$products_event .= 
						"_nzm.run( 'ec:addProduct', {" .
							"'id': '" . $item["id"] . "'," . 
							"'name': '" . $item["name"] . "'," . 
							"'category': '" . $item["category"] . "'," . 
							"'price': '" . $item["price"] . "'," . 
							"'quantity': '" . $item["quantity"] . "'," . 
						"} );";
				}

				$ob_order = [];

				if(!empty($order_id))
				{
					$ob_order = [
						"id" => $order_id,
						"affiliation" => $orderDetails["store_name"],
						"value" => $orderDetails["total"],
						"tax" => $this->getTax($order_totals),
						"shipping" => $this->getShipping($order_totals)
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
					
					var _nzmPluginInfo = '1.1:opencart3';
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
					var ajaxurl = 'https://' + document.location.hostname + '/index.php?route=extension/module/newsman&newsman=getCart.json';
					var documentComparer = '$_domain';
					var documentUrl = document.URL;
					var sameOrigin = (documentUrl.indexOf(documentComparer) !== -1);
					
					let startTime, endTime;
					
					function startTimePassed() {
						startTime = new Date();
					};
					
					startTimePassed();
					
					function endTimePassed() {
						var flag = false;
					
						endTime = new Date();
						var timeDiff = endTime - startTime;
					
						timeDiff /= 1000;
					
						var seconds = Math.round(timeDiff);
					
						if (firstLoad)
							flag = true;
					
						if (seconds >= 5)
							flag = true;
					
						return flag;
					}
					
					if (sameOrigin) {
						NewsmanAutoEvents();
						setInterval(NewsmanAutoEvents, 5000);
					
						detectXHR();
					}
					
					function NewsmanAutoEvents() {
					
						if (!endTimePassed())
							return;
					
						let xhr = new XMLHttpRequest()
					
						if (bufferedXHR || firstLoad) {
					
							var timestamp = "?t=" + Date.now();

							xhr.open('GET', ajaxurl + timestamp, true);
					
							startTimePassed();
					
							xhr.onload = function() {
					
								if (xhr.status == 200 || xhr.status == 201) {
					
									var response = JSON.parse(xhr.responseText);
					
									lastCart = JSON.parse(sessionStorage.getItem('lastCart'));
					
									if (lastCart === null)
										lastCart = {};
					
									//check cache
									if (lastCart.length > 0 && lastCart != null && lastCart != undefined && response.length > 0 && response != null && response != undefined) {
										if (JSON.stringify(lastCart) === JSON.stringify(response)) {
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
					
										addToCart(response);
					
									}
									//send only when on last request, products existed
									else if (response.length == 0 && lastCart.length > 0 && unlockClearCart) {
					
										clearCart();
					
										if (!isProd)
											console.log('newsman remarketing: clear cart sent');
					
									} else {
					
										if (!isProd)
											console.log('newsman remarketing: request not sent');
					
									}
					
									firstLoad = false;
									bufferedXHR = false;
					
								}
					
							}
					
							xhr.send(null);
					
						} else {
							if (!isProd)
								console.log('newsman remarketing: !buffered xhr || first load');
						}
					
					}
					
					function clearCart() {
					
						_nzm.run('ec:setAction', 'clear_cart');
						_nzm.run('send', 'event', 'detail view', 'click', 'clearCart');
					
						sessionStorage.setItem('lastCart', JSON.stringify([]));
					
						unlockClearCart = false;
					
					}
					
					function addToCart(response) {
					
						_nzm.run('ec:setAction', 'clear_cart');
						_nzm.run('send', 'event', 'detail view', 'click', 'clearCart', null, _nzm.createFunctionWithTimeout(function() {
					
							for (var item in response) {
					
								_nzm.run('ec:addProduct',
									response[item]
								);
					
							}
					
							_nzm.run('ec:setAction', 'add');
							_nzm.run('send', 'event', 'UX', 'click', 'add to cart');
					
							sessionStorage.setItem('lastCart', JSON.stringify(response));
							unlockClearCart = true;
					
							if (!isProd)
								console.log('newsman remarketing: cart sent');
					
						}));
					
					}
					
					function detectXHR() {
					
						var proxied = window.XMLHttpRequest.prototype.send;
						window.XMLHttpRequest.prototype.send = function() {
					
							var pointer = this;
							var validate = false;
							var intervalId = window.setInterval(function() {
					
								if (pointer.readyState != 4) {
									return;
								}
					
								var _location = pointer.responseURL;
					
								//own request exclusion
								if (
									pointer.responseURL.indexOf('getCart.json') >= 0 ||
									//magento
									pointer.responseURL.indexOf('/static/') >= 0 ||
									pointer.responseURL.indexOf('/pub/static') >= 0 ||
									pointer.responseURL.indexOf('/customer/section') >= 0
								) {
									validate = false;
								} else {
									if (_location.indexOf(window.location.origin) !== -1)
										validate = true;
								}
					
								if (validate) {
									bufferedXHR = true;
					
									if (!isProd)
										console.log('newsman remarketing: ajax request fired and catched from same domain');
					
									NewsmanAutoEvents();
								}
					
								clearInterval(intervalId);
					
							}, 1);
					
							return proxied.apply(this, [].slice.call(arguments));
						};
					
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

?>