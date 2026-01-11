<?php

namespace Newsman\Remarketing\Action;

/**
 * Class CategoryView Action
 */
class CategoryView extends \Newsman\Remarketing\Action\AbstractAction {
	/**
	 * @return string
	 */
	public function getJs() {
		$path = $this->getRequest()->get['path'] ?? '';
		$category_ids = ($path !== '') ? explode('_', $path) : [];
		$category_id = (int)(end($category_ids) ?: 0);

		$req = $this->getRequest();
		$cfg = $this->getConfig()->getStorageConfig();

		$filter = isset($req->get['filter']) ? (string)$req->get['filter'] : '';
		$sort = isset($req->get['sort']) ? (string)$req->get['sort'] : 'p.sort_order';
		$order = isset($req->get['order']) ? (string)$req->get['order'] : 'ASC';
		$page = isset($req->get['page']) ? (int)$req->get['page'] : 1;

		$theme = (string)$cfg->get('config_theme');
		$limit = (isset($req->get['limit']) && (int)$req->get['limit'] > 0) ? (int)$req->get['limit'] : (int)$cfg->get('theme_' . $theme . '_product_limit');

		$filter_data = [
			'filter_category_id' => $category_id,
			'filter_filter'      => $filter,
			'sort'               => $sort,
			'order'              => $order,
			'start'              => ($page - 1) * $limit,
			'limit'              => $limit,
		];

		$results = $this->getProductModel()->getProducts($filter_data);

		$products = [];
		foreach ($results as $row) {
			$price = (isset($row['special']) && $row['special'] !== null && $row['special'] !== '') ? (float)$row['special'] : (float)$row['price'];

			$products[] = [
				'product_id' => (int)$row['product_id'],
				'name'       => (string)$row['name'],
				'price'      => $price,
			];
		}

		$this->getEvent()->trigger(
			'newsmanremarketing/remarketing_action_category_view/before',
			array(
				&$products,
				&$category_id
			)
		);

		$position = 1;
		$js = '';
		foreach ($products as $product) {
			$js .= $this->escapeHtml($this->getConfig()->getJsTrackRunFunc()) .
				"('ec:addImpression', {" .
				"id: " . (int)$product['product_id'] . "," .
				"name: '" . $this->escapeHtml($product['name']) . "'," .
				"category: '" . $this->escapeHtml($this->getCategoryName($category_id)) . "'," .
				"price: " . number_format((float)$product['price'], 2, '.', '') . "," .
				"list: 'Category Page'," .
				"position: '" . ($position++) . "'" .
				"}); ";
		}

		$this->getEvent()->trigger(
			'newsmanremarketing/remarketing_action_category_view/after',
			array(
				&$js,
				&$products,
				&$category_id
			)
		);

		return $js;
	}
}
