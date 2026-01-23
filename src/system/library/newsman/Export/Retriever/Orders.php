<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Orders
 *
 * @class \Newsman\Export\Retriever\Orders
 */
class Orders extends BaseOrders implements RetrieverInterface {
	/**
	 * Default batch page size
	 */
	public const DEFAULT_PAGE_SIZE = 200;

	/**
	 * @var \Newsman\Export\Order\Status\Mapper
	 */
	protected $order_status;

	public function __construct($registry) {
		parent::__construct($registry);

		$this->order_status = new \Newsman\Export\Order\Status\Mapper($registry);
	}

	/**
	 * Process orders retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$data['default_page_size'] = self::DEFAULT_PAGE_SIZE;

		$this->stores_urls[$store_id] = $this->getConfigStoreBaseUrl($store_id);
		$this->setImageWidth($this->getConfigImageWidth($store_id), $store_id);
		$this->setImageHeight($this->getConfigImageHeight($store_id), $store_id);

		$this->event->trigger('newsman/export_retriever_orders_process_params/before', array(&$data, $store_id));
		$parameters = $this->processListParameters($data, $store_id);
		$this->event->trigger('newsman/export_retriever_orders_process_params/after', array(&$parameters, $data, $store_id));

		$this->logger->info(sprintf('Export orders %s, store ID %s', print_r($parameters, true), $store_id));

		$orders = $this->getOrders($parameters, $store_id);
		if (empty($orders)) {
			return array();
		}

		$this->event->trigger('newsman/export_retriever_orders_process_fetch/after', array(&$orders, $parameters, $store_id));

		$order_ids = array_column($orders, 'order_id');
		$all_order_products = $this->getOrdersProducts($order_ids);
		$all_order_totals = $this->getOrdersTotals($order_ids);

		$product_ids = array();
		foreach ($all_order_products as $op_list) {
			foreach ($op_list as $op) {
				$product_ids[] = $op['product_id'];
			}
		}

		$products_extra_data = $this->prefetchProductsData($product_ids, $store_id);

		$result = array();
		$count_orders = 0;
		foreach ($orders as $order) {
			try {
				$order_id = $order['order_id'];
				$order_products = isset($all_order_products[$order_id]) ? $all_order_products[$order_id] : array();
				$order_totals = isset($all_order_totals[$order_id]) ? $all_order_totals[$order_id] : array();

				$result[] = $this->processOrder($order, $order_products, $order_totals, $products_extra_data, $store_id);
				++$count_orders;
			} catch (\Exception $e) {
				$this->logger->logException($e);
			}
		}

		$this->logger->info(sprintf('Exported orders %s, store ID %s, count %d', print_r($parameters, true), $store_id, $count_orders));

		return $result;
	}

	/**
	 * Process order
	 *
	 * @param array    $order
	 * @param array    $products
	 * @param array    $totals
	 * @param array    $products_extra_data
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function processOrder($order, $products, $totals, $products_extra_data = array(), $store_id = null) {
		$products_data = array();
		$subtotal_amount = 0;

		foreach ($products as $product) {
			$unit_price_with_tax = (float)$product['price'] + (float)$product['tax'];
			$products_data[] = array(
				'id'         => $product['product_id'],
				'quantity'   => (int)$product['quantity'],
				'unit_price' => $unit_price_with_tax,
				'name'       => $product['name'],
			);
			$subtotal_amount += ($unit_price_with_tax * (int)$product['quantity']);
		}

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
						$discount_code = $matches[1];
					}
					break;
			}
		}

		// Calculate the expected total to see if there's a remaining tax (likely from shipping)
		// total = subtotal_gross + shipping_net + shipping_tax - discount
		// We want shipping_gross = shipping_net + shipping_tax
		// shipping_tax = tax_total - product_taxes
		// product_taxes = subtotal_gross - subtotal_net

		$subtotal_net = 0;
		foreach ($totals as $total) {
			if ($total['code'] == 'sub_total') {
				$subtotal_net = $total['value'];
				break;
			}
		}

		$product_taxes = $subtotal_amount - $subtotal_net;

		$tax_amount = 0;
		foreach ($totals as $total) {
			if ($total['code'] == 'tax') {
				$tax_amount += $total['value'];
			}
		}

		// If tax_amount is 0, but we have product taxes, it means either:
		// 1. No taxes at all.
		// 2. Taxes are not recorded with the code 'tax' in order_total.
		// In OpenCart, product taxes from order_product should usually match what's in order_total.
		// If tax_amount from totals is 0, let's trust product_taxes if it's > 0
		if ($tax_amount == 0 && $product_taxes > 0) {
			$tax_amount = $product_taxes;
		}

		$shipping_tax = $tax_amount - $product_taxes;

		if ($shipping_tax > 0) {
			$shipping_amount += $shipping_tax;
		}

		$row = array(
			'id'                   => $order['order_id'],
			'billing_name'         => trim($order['firstname'] . ' ' . $order['lastname']),
			'billing_company_name' => $order['payment_company'],
			'billing_phone'        => $this->telephone->clean($order['telephone']),
			'customer_email'       => $order['email'],
			'shipping_amount'      => (float)$shipping_amount,
			'tax_amount'           => (float)$tax_amount,
			'total_amount'         => (float)$order['total'],
			'currency'             => $order['currency_code'],
			'subtotal_amount'      => (float)$subtotal_amount,
			'discount'             => (float)$discount,
			'discount_code'        => $discount_code,
			'status'               => $this->order_status->map($order['order_status_id'], $order['order_status_name'], $store_id),
			'date_created'         => $order['date_added'],
			'date_modified'        => $order['date_modified'],
			'products'             => $products_data,
		);

		$this->event->trigger('newsman/export_retriever_orders_process_order', array(&$row, $order, $store_id));

		return $row;
	}

	/**
	 * Get order products
	 *
	 * @param array $order_ids
	 *
	 * @return array
	 */
	protected function getOrdersProducts($order_ids) {
		if (empty($order_ids)) {
			return array();
		}

		/** @var \stdClass $query */
		$query = $this->registry->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id IN (" . implode(',', array_map('intval', $order_ids)) . ")");

		$order_products = array();
		foreach ($query->rows as $row) {
			$order_products[$row['order_id']][] = $row;
		}

		return $order_products;
	}

	/**
	 * Get order products
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	protected function getOrderProducts($order_id) {
		/** @var \stdClass $query */
		$query = $this->registry->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	/**
	 * Pre-fetch products data (images and SEO URLs)
	 *
	 * @param array $product_ids
	 * @param int   $store_id
	 *
	 * @return array
	 */
	protected function prefetchProductsData($product_ids, $store_id) {
		$data = array(
			'images'   => array(),
			'seo_urls' => array()
		);

		if (empty($product_ids)) {
			return $data;
		}

		$product_ids = array_unique(array_map('intval', $product_ids));
		$batches = array_chunk($product_ids, 300);

		foreach ($batches as $batch) {
			$ids_csv = implode(',', $batch);

			// Fetch images
			/** @var \stdClass $query_images */
			$query_images = $this->registry->db->query("SELECT product_id, image FROM " . DB_PREFIX . "product WHERE product_id IN (" . $ids_csv . ")");
			foreach ($query_images->rows as $row) {
				$data['images'][$row['product_id']] = $row['image'];
			}

			// Fetch SEO URLs
			if ($this->getConfigSeoUrl($store_id)) {
				$query_params = array();
				foreach ($batch as $id) {
					$query_params[] = "product_id=" . $id;
				}

				/** @var \stdClass $query_seo */
				$query_seo = $this->registry->db->query(
					"SELECT query, keyword FROM " . DB_PREFIX . "seo_url " .
					"WHERE `query` IN ('" . implode("','", $query_params) . "') " .
					"AND store_id = '" . (int)$store_id . "' " .
					"AND language_id = '" . $this->getLanguageIdByStoreId($store_id) . "'"
				);

				foreach ($query_seo->rows as $row) {
					$product_id = (int)str_replace('product_id=', '', $row['query']);
					$data['seo_urls'][$product_id] = $row['keyword'];
				}
			}
		}

		return $data;
	}

	/**
	 * Get order totals
	 *
	 * @param array $order_ids
	 *
	 * @return array
	 */
	protected function getOrdersTotals($order_ids) {
		if (empty($order_ids)) {
			return array();
		}

		/** @var \stdClass $query */
		$query = $this->registry->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id IN (" . implode(',', array_map('intval', $order_ids)) . ") ORDER BY sort_order ASC");

		$order_totals = array();
		foreach ($query->rows as $row) {
			$order_totals[$row['order_id']][] = $row;
		}

		return $order_totals;
	}

	/**
	 * Get order totals
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	protected function getOrderTotals($order_id) {
		/** @var \stdClass $query */
		$query = $this->registry->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	/**
	 * Get product image
	 *
	 * @param int   $product_id
	 * @param array $products_extra_data
	 * @param int   $store_id
	 *
	 * @return string
	 */
	protected function getProductImage($product_id, $products_extra_data, $store_id) {
		$image = isset($products_extra_data['images'][$product_id]) ? $products_extra_data['images'][$product_id] : '';

		if ($image) {
			$info = pathinfo($image);
			$dimension = $this->getImageWidth() . 'x' . $this->getImageHeight();
			$path = 'image/cache/' . (($info['dirname'] !== '.') ? $info['dirname'] . '/' : '') . $info['filename'] . '-' . $dimension . '.' . $info['extension'];

			return $this->stores_urls[$store_id] . $path;
		}

		return $this->stores_urls[$store_id] . 'image/placeholder.png';
	}

	/**
	 * Get product URL
	 *
	 * @param int   $product_id
	 * @param array $products_extra_data
	 * @param int   $store_id
	 *
	 * @return string
	 */
	protected function getProductUrl($product_id, $products_extra_data, $store_id) {
		$url = 'index.php?route=product/product&product_id=' . (int)$product_id;

		if (isset($products_extra_data['seo_urls'][$product_id]) && $this->getConfigSeoUrl($store_id)) {
			$url = $products_extra_data['seo_urls'][$product_id];
		}

		return $this->stores_urls[$store_id] . $url;
	}

	/**
	 * Get allowed request parameters
	 *
	 * @return array
	 */
	public function getWhereParametersMapping() {
		$return = array(
			'created_at'  => array(
				'field' => 'o.date_added',
				'quote' => true,
				'type'  => 'string'
			),
			'modified_at' => array(
				'field' => 'o.date_modified',
				'quote' => true,
				'type'  => 'string'
			),
			'order_id'    => array(
				'field' => 'o.order_id',
				'quote' => false,
				'type'  => 'int'
			),
			'order_ids'   => array(
				'field'       => 'o.order_id',
				'quote'       => false,
				'multiple'    => true,
				'force_array' => true,
				'type'        => 'int'
			)
		);

		return array_merge(parent::getWhereParametersMapping(), $return);
	}

	/**
	 * Get allowed sort fields
	 *
	 * @return array
	 */
	public function getAllowedSortFields() {
		return array_merge(
			parent::getAllowedSortFields(),
			array(
				'created_at'  => 'o.date_added',
				'modified_at' => 'o.date_modified',
				'order_id'    => 'o.order_id'
			)
		);
	}
}
