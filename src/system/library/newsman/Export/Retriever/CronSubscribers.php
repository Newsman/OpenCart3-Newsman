<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Cron Subscribers to API Newsman
 *
 * @class \Newsman\Export\Retriever\CronSubscribers
 */
class CronSubscribers extends SendSubscribers implements RetrieverInterface {
	/**
	 * Default batch page size
	 */
	public const DEFAULT_PAGE_SIZE = 1000;

	/**
	 * Process subscribers retriever
	 *
	 * @param array    $data
	 * @param null|int $store_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function process($data = array(), $store_id = null) {
		$data['_internal_is_subscribers'] = true;

		if (isset($data['limit'])) {
			return parent::process($data, $store_id);
		}

		// Export all subscribers in batches.
		$data['limit'] = self::DEFAULT_PAGE_SIZE;
		$this->event->trigger('newsman/export_retriever_cron_subscribers_process_params/before', array(&$data, $store_id));

		if (isset($data['_internal_is_subscribers'])) {
			$this->setIsSubscriber($data['_internal_is_subscribers']);
			unset($data['_internal_is_subscribers']);
		}

		$parameters = $this->processListParameters($data, $store_id);
		$this->event->trigger('newsman/export_retriever_cron_subscribers_process_params/after', array(&$parameters, $data, $store_id));

		$return = array();
		$count = $this->getCountCustomers($parameters, $store_id);
		for ($start = 0; $start < $count; $start += $data['limit']) {
			$data['start'] = $start;
			$return[] = parent::process($data, $store_id);
		}

		return $return;
	}
}
