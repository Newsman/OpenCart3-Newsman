<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Products Feed
 *
 * @class \Newsman\Export\Retriever\ProductsFeed
 */
class ProductsFeed extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Default batch page size
	 */
	public const DEFAULT_PAGE_SIZE = 1000;

	/**
	 * Additional product attributes
	 *
	 * @var array
	 */
	protected $additional_attributes = array();

	/**
	 * @var array
	 */
	protected $categories = array();

	/**
	 * @var string
	 */
	protected $trigger_prefix = 'products_feed';

	/**
	 * Process products feed retriever
	 *
	 * @param array    $data
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$data['default_page_size'] = self::DEFAULT_PAGE_SIZE;

		$this->stores_urls[$store_id] = $this->getConfigStoreBaseUrl($store_id);
		$this->setImageWidth($this->getConfigImageWidth($store_id), $store_id);
		$this->setImageHeight($this->getConfigImageHeight($store_id), $store_id);

		$this->event->trigger('newsman/export_retriever_' . $this->trigger_prefix . '_process_params/before', array(&$data, $store_id));
		$parameters = $this->processListParameters($data, $store_id);
		$this->event->trigger('newsman/export_retriever_' . $this->trigger_prefix . '_process_params/after', array(&$parameters, $data, $store_id));

		$this->logger->info(sprintf('Export products %s, store ID %s', print_r($parameters, true), $store_id));

		$this->getCategories($store_id);
		$products = $this->getProducts($parameters, $store_id);
		if (empty($products)) {
			return array();
		}
		$this->event->trigger('newsman/export_retriever_' . $this->trigger_prefix . '_process_fetch/after', array(&$products, $parameters, $store_id));

		$result = array();
		foreach ($products as $product) {
			try {
				$result[] = $this->processProduct($product, $store_id);
			} catch (\Exception $e) {
				$this->logger->logException($e);
			}
		}

		$this->logger->info(sprintf('Exported products %s, store ID %s', print_r($parameters, true), $store_id));

		return $result;
	}

	/**
	 * Get products
	 *
	 * @param array    $params
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function getProducts($params = array(), $store_id = null) {
		$sql = "SELECT
			p.product_id AS product_id,
			p.quantity AS quantity,
			p.stock_status_id AS stock_status_id,
			p.image AS image,
			p.price AS price,
			p.tax_class_id AS tax_class_id,
			p.status AS status,
			p.date_added AS date_added,
			p.date_modified AS date_modified,
			pd.name AS name,
			(
				SELECT 
				    price
				FROM " . DB_PREFIX . "product_discount pd2
				WHERE
					pd2.product_id = p.product_id AND 
					pd2.customer_group_id = '" . $this->getLanguageIdByStoreId($store_id) . "' AND 
					pd2.quantity = '1' AND 
					((pd2.date_start = DATE('0000-00-00') OR pd2.date_start < NOW()) AND (pd2.date_end = DATE('0000-00-00') OR pd2.date_end > NOW()))
					ORDER BY pd2.priority ASC, pd2.price ASC
					LIMIT 1
			) AS discount,
			(
				SELECT
					price
				FROM " . DB_PREFIX . "product_special ps
				WHERE
					ps.product_id = p.product_id AND
					ps.customer_group_id = '" . $this->getLanguageIdByStoreId($store_id) . "' AND
					((ps.date_start = DATE('0000-00-00') OR ps.date_start < NOW()) AND (ps.date_end = DATE('0000-00-00') OR ps.date_end > NOW()))
				ORDER BY ps.priority ASC, ps.price ASC
				LIMIT 1
			) AS special";
		$sql .= " FROM " . DB_PREFIX . "product p";
		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)";
		$sql .= " LEFT JOIN " . DB_PREFIX . "product_to_store p2s
			ON (p.product_id = p2s.product_id)
			WHERE
				pd.language_id = '" . $this->getLanguageIdByStoreId($store_id) . "' AND
				p2s.store_id = " . $store_id;

		// Add filters to the SELECT statement.
		$where = array();
		foreach ($params['filters'] as $filter) {
			if (is_array($filter)) {
				$where[] = implode(' AND ', $filter);
			} else {
				$where[] = $filter;
			}
		}

		if (!empty($where)) {
			$sql .= ' AND ' . implode(' AND ', $where);
		}

		$sql .= " GROUP BY p.product_id";

		// Add sorting to the SELECT statement.
		if (isset($params['sort']) && isset($params['order'])) {
			$sql .= " ORDER BY " . $params['sort'] . ' ' . $params['order'];
		}

		// Add start and limit to the SELECT statement.
		$start = 0;
		if (isset($params['start']) && $params['start'] >= 0) {
			$start = (int)$params['start'];
		}
		$limit = $params['default_page_size'];
		if (isset($params['limit']) && $params['limit'] >= 1) {
			$limit = (int)$params['limit'];
		}
		$sql .= " LIMIT " . $start . "," . $limit;

		/** @var \stdClass $query */
		$query = $this->registry->db->query($sql);

		if ($query->num_rows < 1) {
			return array();
		}

		$return = $query->rows;
		unset($query);

		// Get categories IDs from the products set.
		$product_categories = $this->getProductsCategories(array_column($return, 'product_id'));
		foreach ($return as &$row) {
			$row['categories'] = array();
			if (isset($product_categories[$row['product_id']])) {
				$row['categories'] = $product_categories[$row['product_id']];
			}
		}
		unset($product_categories);
		unset($row);

		if (!$this->getConfigSeoUrl($store_id)) {
			return $return;
		}

		// Get SEO URLs for the products set.
		$product_ids_where = array();
		foreach ($return as $row) {
			$product_ids_where[] = 'product_id=' . $row['product_id'];
		}
		$batches = array_chunk($product_ids_where, 100);
		unset($product_ids_where);

		$seo_urls = array();
		foreach ($batches as $batch) {
			/** @var \stdClass $query */
			$query = $this->registry->db->query(
				"SELECT query, keyword, store_id FROM " . DB_PREFIX . "seo_url " .
				"WHERE `query` IN ('" . implode("','", $batch) . "')" .
				"    AND store_id = " . $store_id . " " .
				"    AND language_id = '" . $this->getLanguageIdByStoreId($store_id) . "' "
			);
			if ($query->num_rows < 1) {
				continue;
			}

			foreach ($query->rows as $row) {
				$product_id = (int)str_replace('product_id=', '', $row['query']);
				if (!isset($seo_urls[$product_id])) {
					$seo_urls[$product_id] = $row['keyword'];
				}
			}
		}
		unset($batches, $query);
		foreach ($return as &$row) {
			if (isset($seo_urls[$row['product_id']])) {
				$row['url'] = $seo_urls[$row['product_id']];
			}
		}

		return $return;
	}

	/**
	 * Get product categories
	 *
	 * @param array $product_ids
	 *
	 * @return array
	 */
	public function getProductsCategories($product_ids) {
		$product_categories = array();

		if (empty($product_ids)) {
			return $product_categories;
		}

		$batches = array_chunk($product_ids, 300);
		foreach ($batches as $batch) {
			$sql = "SELECT product_id, category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id IN (" . implode(',', array_map('intval', $batch)) . ")";
			/** @var \stdClass $query */
			$query = $this->registry->db->query($sql);

			foreach ($query->rows as $row) {
				if (!isset($product_categories[$row['product_id']])) {
					$product_categories[$row['product_id']] = array();
				}
				$product_categories[$row['product_id']][] = $row['category_id'];
			}
		}

		return $product_categories;
	}

	/**
	 * Get categories
	 *
	 * @param int $store_id
	 *
	 * @return array
	 */
	public function getCategories($store_id) {
		if (isset($this->categories[$store_id])) {
			return $this->categories[$store_id];
		}

		$sql = "SELECT c.category_id, c.parent_id, cd.name 
			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) 
			WHERE c.status = '1' 
				AND c2s.store_id = '" . (int)$store_id . "' 
				AND cd.language_id = '" . $this->getLanguageIdByStoreId($store_id) . "'";

		/** @var \stdClass $query */
		$query = $this->registry->db->query($sql);

		$this->categories[$store_id] = array();
		foreach ($query->rows as $row) {
			$this->categories[$store_id][$row['category_id']] = $row;
		}

		return $this->categories[$store_id];
	}

	/**
	 * Get a category path from bottom to top
	 *
	 * @param int $category_id
	 * @param int $store_id
	 *
	 * @return array
	 */
	public function getCategoryPath($category_id, $store_id) {
		$path = array();

		if (!isset($this->categories[$store_id])) {
			$this->getCategories($store_id);
		}

		$current_id = $category_id;
		$fail_safe = 0;

		while (isset($this->categories[$store_id][$current_id]) && $fail_safe < 30) {
			$category = $this->categories[$store_id][$current_id];
			$path[] = $category;

			if ($category['parent_id'] == 0 || $category['parent_id'] == $current_id) {
				break;
			}

			$current_id = $category['parent_id'];
			$fail_safe++;
		}

		return $path;
	}

	/**
	 * Get allowed request parameters
	 *
	 * @return array
	 */
	public function getWhereParametersMapping() {
		$return = array(
			'created_at'  => array(
				'field' => 'p.date_added',
				'quote' => true,
				'type'  => 'string'
			),
			'modified_at' => array(
				'field' => 'p.date_modified',
				'quote' => true,
				'type'  => 'string'
			),
			'product_id'  => array(
				'field' => 'p.product_id',
				'quote' => false,
				'type'  => 'int'
			),
			'product_ids' => array(
				'field'       => 'p.product_id',
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
				'created_at'  => 'p.date_added',
				'modified_at' => 'p.date_modified',
				'product_id'  => 'p.product_id'
			)
		);
	}

	/**
	 * Process product
	 *
	 * @param array    $product
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function processProduct($product, $store_id = null) {
		$config = $this->getConfigCache($store_id);
		$config_tax = (isset($config['config_tax']) && $config['config_tax'] > 0) ? $config['config_tax'] : false;

		if ($config_tax !== false) {
			if ($product['price'] > 0) {
				$product['price'] = $this->registry->get('tax')->calculate($product['price'], $product['tax_class_id'], $config_tax);
			}

			if (isset($product['discount']) && $product['discount'] > 0) {
				$product['discount'] = $this->registry->get('tax')->calculate($product['discount'], $product['tax_class_id'], $config_tax);
			}

			if (isset($product['special']) && $product['special'] > 0) {
				$product['special'] = $this->registry->get('tax')->calculate($product['special'], $product['tax_class_id'], $config_tax);
			}
		}

		$row = array(
			'id'   => $product['product_id'],
			'url'  => $this->stores_urls[$store_id] . 'index.php?route=product/product&product_id=' . $product['product_id'],
			'name' => $product['name']
		);

		// Set SEO URL
		if (isset($product['url']) && $this->getConfigSeoUrl($store_id)) {
			$row['url'] = $this->stores_urls[$store_id] . $product['url'];
		}

		// Set prices
		if ($product['discount'] > 0 || $product['special'] > 0) {
			$row['price_full'] = round($product['price'], 2);
			if ($product['discount'] > 0) {
				$row['price_discount'] = round($product['discount'], 2);
			}
			if ($product['special'] > 0) {
				$row['price_discount'] = round($product['special'], 2);
			}
			if ($row['price_full'] < $row['price_discount']) {
				unset($row['price_full']);
				unset($row['price_discount']);
				$row['price'] = round($product['price'], 2);
			}
		} else {
			$row['price'] = round($product['price'], 2);
		}

		// Set product image
		if (!empty($product['image'])) {
			$info = pathinfo($product['image']);
			$dimension = $this->getImageWidth() . 'x' . $this->getImageHeight();
			$path = 'image/cache/' . (($info['dirname'] !== '.') ? $info['dirname'] . '/' : '') . $info['filename'] . '-' . $dimension . '.' . $info['extension'];
			$image_url = $this->stores_urls[$store_id] . $path;
		} else {
			$image_url = $this->stores_urls[$store_id] . 'image/placeholder.png';
		}
		$row['image_url'] = $image_url;

		// Set categories data
		$row['category'] = array();
		$row['subcategories'] = array();
		$categories = array();
		$levels = array();
		$max_level = 0;
		$max_category_level = false;
		foreach ($product['categories'] as $category_id) {
			$categories[$category_id] = $this->getCategoryPath($category_id, $store_id);
			$levels[$category_id] = count($categories[$category_id]);
			if ($levels[$category_id] > $max_level) {
				$max_level = $levels[$category_id];
				$max_category_level = $category_id;
			}
		}

		if ($max_category_level !== false) {
			$row['category'] = html_entity_decode($categories[$max_category_level][0]['name']);
			$subcategories = array();
			foreach ($categories as $category) {
				$subcategories[] = array_map('html_entity_decode', array_column(array_reverse($category), 'name'));
			}
			$row['subcategories'] = $subcategories;
		}

		// Set stock data
		if ($product['status']) {
			$row['in_stock'] = ($product['quantity'] > 0) ? 1 : ($this->getConfigStockCheckout($store_id) ? 1 : 0);
		} else {
			$row['in_stock'] = 0;
		}
		$row['stock_quantity'] = (int)$product['quantity'];
		$row['variants'] = '';

		foreach ($this->getAdditionalAttributes() as $attribute_name) {
			if (!empty($product[$attribute_name])) {
				$row[$attribute_name] = $product[$attribute_name];
			} else {
				$row[$attribute_name] = '';
			}
		}

		$this->event->trigger('newsman/export_retriever_' . $this->trigger_prefix . '_process_product/after', array(&$row, $product, $store_id));

		return $row;
	}

	/**
	 * Set additional attributes
	 *
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function setAdditionalAttributes($attributes) {
		$this->additional_attributes = $attributes;

		return $this;
	}

	/**
	 * Get additional attributes
	 *
	 * @return array
	 */
	public function getAdditionalAttributes() {
		$this->event->trigger('newsman/export_retriever_' . $this->trigger_prefix . '_additional_attributes/after', array(&$this->additional_attributes));

		return $this->additional_attributes;
	}
}
