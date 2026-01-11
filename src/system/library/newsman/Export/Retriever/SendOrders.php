<?php

namespace Newsman\Export\Retriever;

use Newsman\Export\Order\Mapper as OrderMapper;

/**
 * Class Export Retriever Send Orders
 *
 * @class \Newsman\Export\Retriever\SendOrders
 */
class SendOrders extends BaseOrders implements RetrieverInterface {
	/**
	 * Default batch page size
	 */
	public const DEFAULT_PAGE_SIZE = 200;

	/**
	 * Default batch API size
	 */
	public const BATCH_SIZE = 500;

	/**
	 * Order Mapper
	 *
	 * @var OrderMapper
	 */
	protected $order_mapper;

	/**
	 * Class construct
	 */
	public function __construct($registry) {
		parent::__construct($registry);
		$this->order_mapper = new OrderMapper($registry);
	}

	/**
	 * Process send orders retriever
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

		$this->logger->info(sprintf('Send orders %s, store ID %s', print_r($parameters, true), $store_id));

		$orders = $this->getOrders($data, $store_id);

		if (empty($orders)) {
			return array();
		}

		$result = array();
		$count_orders = 0;
		foreach ($orders as $order) {
			try {
				$result[] = $this->processOrder($order, $store_id);
				++$count_orders;
			} catch (\Exception $e) {
				$this->logger->logException($e);
			}
		}

		unset($orders);
		$batches = array_chunk($result, self::BATCH_SIZE);
		unset($result);

		$count = 0;
		$api_results = array();
		foreach ($batches as $batch) {
			try {
				$context = new \Newsman\Service\Context\Remarketing\SaveOrders();
				$context->setStoreId($store_id)
					->setListId($this->config->getListId($store_id))
					->setOrders($batch);

				$export = new \Newsman\Service\Remarketing\SaveOrders($this->registry);
				$api_results[] = $export->execute($context);

				$count += count($batch);

				unset($context);
				unset($export);
			} catch (\Exception $e) {
				$this->logger->logException($e);
			}
		}

		$this->logger->info(sprintf('Sent orders %s, store ID %s', print_r($batches, true), $store_id));

		return array(
			'status'  => sprintf(
				'Sent to NewsMAN %d orders out of a total of %d.',
				$count,
				$count_orders
			),
			'results' => $api_results,
		);
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

	/**
	 * Process order
	 *
	 * @param array    $order Order data.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function processOrder($order, $store_id = null) {
		$order_id = $order['order_id'];
		$products = $this->getOrderProducts($order_id);
		$totals = $this->getOrderTotals($order_id);

		$order_data = $this->order_mapper->toArray($order, $products, $totals);
		$row = $order_data['details'];
		$row['products'] = $order_data['products'];

		return $row;
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
	 * Get count orders
	 *
	 * @param array    $params
	 * @param null|int $store_id
	 *
	 * @return int
	 */
	public function getCountOrders($params = array(), $store_id = null) {
		return $this->getOrders($params, $store_id, true);
	}

	/**
	 * Get batch size
	 *
	 * @return int
	 */
	public function getBatchSize() {
		return self::BATCH_SIZE;
	}
}
