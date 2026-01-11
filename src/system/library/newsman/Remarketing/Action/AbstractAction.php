<?php

namespace Newsman\Remarketing\Action;

/**
 * Class Abstract Action
 */
class AbstractAction extends \Newsman\Nzmbase {
	/**
	 * @var \Request
	 */
	protected $request;

	/**
	 * @var \DB
	 */
	protected $db;

	/**
	 * @var \ModelCatalogProduct
	 */
	protected $product_model;

	/**
	 * @var \ModelCatalogCategory
	 */
	protected $category_model;

	/**
	 * @var \ModelCheckoutOrder
	 */
	protected $checkout_order_model;

	/**
	 * Get the deepest category ID from URL path or product ID.
	 *
	 * @return int
	 */
	protected function getDeepestCategoryId() {
		// From URL path=
		if (!empty($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);

			return (int)array_pop($parts);
		}

		// On the product display page
		$product_id = $this->request->get['product_id'];
		if (empty($product_id)) {
			return 0;
		}

		$categories = $this->getProductModel()->getCategories($product_id);
		if (empty($categories)) {
			return 0;
		}

		$deepest_id = 0;
		$deepest_level = -1;
		$depth_cache = array();

		foreach ($categories as $category) {
			$cid = (int)$category['category_id'];
			$level = $this->getCategoryDepthFromPathTable($cid);

			// Fallback when level = 0
			if ($level <= 0) {
				if (isset($depth_cache[$cid])) {
					$level = $depth_cache[$cid];
				} else {
					$level = $this->getCategoryDepthByParent($cid);
					$depth_cache[$cid] = $level;
				}
			}

			if ($level > $deepest_level) {
				$deepest_level = $level;
				$deepest_id = $cid;
			}
		}

		// Last fallback, all categories have level 0, then pick the highest ID.
		if ($deepest_level <= 0) {
			$max_id = 0;
			foreach ($categories as $category) {
				$max_id = max($max_id, (int)$category['category_id']);
			}

			return $max_id;
		}

		return $deepest_id;
	}

	/**
	 * Get the category depth from the category path table.
	 *
	 * @param int $category_id
	 *
	 * @return int
	 */
	protected function getCategoryDepthFromPathTable($category_id) {
		/** @var \stdClass $query */
		$query = $this->getDb()->query("SELECT MAX(`level`) AS depth FROM " . DB_PREFIX
			. "category_path WHERE `category_id` = '" . $this->getDb()->escape((int)$category_id) . "'");

		return ($query->num_rows > 0) ? (int)$query->row['depth'] : 0;
	}

	/**
	 * Get the category depth by traversing the parent category IDs.
	 *
	 * @param int $category_id
	 *
	 * @return int
	 */
	protected function getCategoryDepthByParent($category_id) {
		$level = 0;
		$visited = array();
		$current = (int)$category_id;

		$fail_safe = 0;
		while ($current && !in_array($current, $visited) && ((++$fail_safe) < 20)) {
			$visited[] = $current;
			/** @var \stdClass $query */
			$query = $this->getDb()->query("SELECT parent_id FROM " . DB_PREFIX . "category WHERE `category_id` = '"
				. $this->getDb()->escape((int)$current) . "' LIMIT 1");

			if ($query->num_rows <= 0) {
				break;
			}

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
	 * Get category name by ID.
	 *
	 * @param int $category_id
	 * @param int $language_id
	 *
	 * @return string
	 */
	protected function getCategoryName($category_id, $language_id = null) {
		if (empty($category_id)) {
			return '';
		}

		if ($language_id === null) {
			$language_id = (int)$this->config->getStorageConfig()->get('config_language_id');
		}

		/** @var \stdClass $query */
		$query = $this->getDb()->query("SELECT name FROM " . DB_PREFIX . "category_description WHERE `category_id` = '"
			. (int)$category_id . "'AND `language_id` = '" . $this->getDb()->escape((int)$language_id) . "'LIMIT 1");

		return ($query->num_rows > 0) ? $query->row['name'] : '';
	}

	/**
	 * Get a category path by ID.
	 *
	 * @param int $category_id
	 *
	 * @return string
	 */
	protected function getCategoryPath($category_id) {
		$path = '';
		$category = $this->getCategoryModel()->getCategory($category_id);

		if (!array_key_exists('name', $category)) {
			return '';
		}

		if ($category['parent_id'] != 0) {
			$path .= $this->getCategoryPath($category['parent_id']) . ' / ';
		}

		$path .= $category['name'];

		return $path;
	}

	/**
	 * Maps Opencart product data to Google Analytics product structure
	 *
	 * @param int   $order_id
	 * @param array $product
	 *
	 * @return array
	 */
	protected function getProduct($order_id, $product) {
		$oc_product = $this->getProductModel()->getProduct($product["product_id"]);

		// Get product options
		$product["variant"] = '';
		$variants = $this->getCheckoutOrderModel()->getOrderOptions($order_id, $product["order_product_id"]);
		foreach ($variants as $variant)
			$product["variant"] = $variant["value"] . " | ";
		if ($product["variant"]) {
			$product["variant"] = substr($product["variant"], 0, -3);
		}

		// Get a category path
		$oc_categories = $this->getProductModel()->getCategories($product["product_id"]);
		$oc_category = array();
		if (sizeof($oc_categories) > 0) {
			$oc_category = $this->getCategoryModel()->getCategory($oc_categories[0]["category_id"]);
			if (sizeof($oc_category) > 0) {
				$oc_category["path"] = $this->getCategoryPath($oc_category['category_id']);
			} else {
				$oc_category["path"] = '';
			}
		}

		$return = [
			"id"       => $product["product_id"],
			"name"     => $product["name"],
			"sku"      => $oc_product["sku"],
			"brand"    => $oc_product["manufacturer"],
			"category" => $oc_category["path"],
			"variant"  => $product["variant"],
			"quantity" => $product["quantity"],
			"price"    => $product["price"]
		];

		return $return;
	}

	/**
	 * @param \Request $request
	 *
	 * @return $this
	 */
	public function setRequest($request) {
		$this->request = $request;

		return $this;
	}

	/**
	 * @return \Request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * @param \DB $db
	 *
	 * @return $this
	 */
	public function setDb($db) {
		$this->db = $db;

		return $this;
	}

	/**
	 * @return \DB
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 * @param \ModelCatalogProduct $model
	 *
	 * @return $this
	 */
	public function setProductModel($model) {
		$this->product_model = $model;

		return $this;
	}

	/**
	 * @return \ModelCatalogProduct
	 */
	public function getProductModel() {
		return $this->product_model;
	}

	/**
	 * @param \ModelCatalogCategory $model
	 *
	 * @return $this
	 */
	public function setCategoryModel($model) {
		$this->category_model = $model;

		return $this;
	}

	/**
	 * @return \ModelCatalogCategory
	 */
	public function getCategoryModel() {
		return $this->category_model;
	}

	/**
	 * @param \ModelCheckoutOrder $model
	 *
	 * @return $this
	 */
	public function setCheckoutOrderModel($model) {
		$this->checkout_order_model = $model;

		return $this;
	}

	/**
	 * @return \ModelCheckoutOrder
	 */
	public function getCheckoutOrderModel() {
		return $this->checkout_order_model;
	}
}
