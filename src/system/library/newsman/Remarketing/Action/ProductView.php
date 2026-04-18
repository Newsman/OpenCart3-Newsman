<?php

namespace Newsman\Remarketing\Action;

/**
 * Class ProductView Action
 */
class ProductView extends \Newsman\Remarketing\Action\AbstractAction {
	/**
	 * @return string
	 */
	public function getJs() {
		$product_id = $this->getRequest()->get['product_id'];

		$product = $this->getProductModel()->getProduct($product_id);
		$categories = $this->getProductModel()->getCategories($product_id);

		if (sizeof($categories) > 0) {
			reset($categories);
			$category = current($categories);
			$category = $this->getCategoryModel()->getCategory($category['category_id']);
			$category['path'] = '';
			if (sizeof($category) > 0) {
				$category['path'] = $this->getCategoryPath($category['category_id']);
			}
		}

		if (!(!empty($product) && is_array($product))) {
			return '';
		}

		$this->getEvent()->trigger(
			'newsmanremarketing/remarketing_action_product_view/before',
			array(
				&$product,
				&$categories
			)
		);

		$js = "_nzm.run('ec:addProduct', {
		        'id': '" . (isset($product['product_id']) ? $this->escapeHtml($product['product_id']) : '') . "',
		        'name': '" . (isset($product['name']) ? $this->escapeHtml($product['name']) : '') . "',
		        'category': '" . $this->escapeHtml($this->getCategoryName($this->getDeepestCategoryId())) . "',
		        'price': '" . (isset($product['price']) ? $this->escapeHtml($product['price']) : '') . "',
		        'list': 'Product Page'
			}); ";
		$js .= "_nzm.run('ec:setAction', 'detail');";

		$this->getEvent()->trigger(
			'newsmanremarketing/remarketing_action_product_view/after',
			array(
				&$js,
				&$product,
				&$categories
			)
		);

		return $js;
	}
}
