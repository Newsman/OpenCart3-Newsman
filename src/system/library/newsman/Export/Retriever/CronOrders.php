<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Cron Orders to API Newsman
 *
 * @class \Newsman\Export\Retriever\CronOrders
 */
class CronOrders extends SendOrders implements RetrieverInterface {
	/**
	 * Default batch page size
	 */
	public const DEFAULT_PAGE_SIZE = 200;

	/**
	 * Process Orders retriever
	 *
	 * @param array    $data
	 * @param null|int $store_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function process($data = array(), $store_id = null) {
		if (isset($data['limit'])) {
			return parent::process($data, $store_id);
		}

		$last_days = (isset($data['last-days'])) ? (int)$data['last-days'] : false;
		if ($last_days !== false) {
			$data['created_at'] = array();
			$data['created_at']['from'] = date('Y-m-d', strtotime('-' . $last_days . ' days'));
		}

		// Export all orders in batches.
		$data['limit'] = self::DEFAULT_PAGE_SIZE;
		$this->event->trigger('newsman/export_retriever_cron_orders_process_params/before', array(&$data, $store_id));
		$parameters = $this->processListParameters($data, $store_id);
		$this->event->trigger('newsman/export_retriever_cron_orders_process_params/after', array(&$parameters, $data, $store_id));

		$return = array();
		$count = $this->getCountOrders($parameters, $store_id);
		for ($start = 0; $start < $count; $start += $data['limit']) {
			$data['start'] = $start;
			$return[] = parent::process($data, $store_id);
		}

		return $return;
	}
}
