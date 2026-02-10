<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Products
 *
 * @class \Newsman\Export\Retriever\Products
 */
class Products extends ProductsFeed {
	/**
	 * @var string
	 */
	protected $trigger_prefix = 'product';

	/**
	 * Process product
	 *
	 * @param array $product
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function processProduct($product, $store_id = null)
	{
		$row = parent::processProduct($product, $store_id);
		if (isset($row['price_discount']) || isset($row['price_full'])) {
			$row['price'] = $row['price_discount'];
			$row['price_old'] = $row['price_full'];
			unset($row['price_discount']);
			unset($row['price_full']);
		} else {
			$row['price_old'] = '';
		}

		return $row;
	}
}
