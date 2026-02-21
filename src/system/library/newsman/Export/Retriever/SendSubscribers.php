<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Cron Subscribers to API Newsman
 *
 * @class \Newsman\Export\Retriever\SendSubscribers
 * @deprecated
 */
class SendSubscribers extends Users {
	/**
	 * Default batch API size
	 */
	public const BATCH_SIZE = 9000;

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
		$data['confirmed'] = 1;
		$data['_internal_is_subscribers'] = true;

		$subscribers = parent::process($data, $store_id);
		if (empty($subscribers)) {
			return array('status' => 'No subscribers found.');
		}

		$result = array();
		$count_subscribers = 0;
		foreach ($subscribers as $subscriber) {
			try {
				$adata = $this->processSubscriber($subscriber, $store_id);
				$result[] = $adata;
				++$count_subscribers;
			} catch (\Exception $e) {
				$this->logger->logException($e);
			}
		}
		unset($subscribers);

		$batches = array_chunk($result, self::BATCH_SIZE);
		unset($result);

		$count = 0;
		$api_results = array();
		foreach ($batches as $batch) {
			try {
				$context = new \Newsman\Service\Context\ExportCsvSubscribers();
				$context->setStoreId($store_id)
					->setListId($this->config->getListId($store_id))
					->setSegmentId($this->config->getSegmentId($store_id))
					->setCsvData($batch)
					->setAdditionalFields(array_keys($this->additional_attributes));

				$export = new \Newsman\Service\ExportCsvSubscribers($this->registry);
				$api_results[] = $export->execute($context);

				$count += count($batch);

				unset($context);
				unset($export);
			} catch (\Exception $e) {
				$this->logger->logException($e);
			}
		}

		$this->logger->info(
			sprintf(
				'Exported subscribers %s, store ID %s',
				print_r($batches, true),
				$store_id
			)
		);

		return array(
			'status'  => sprintf(
				'Sent to NewsMAN %d subscribers out of a total of %d.',
				$count,
				$count_subscribers
			),
			'results' => $api_results
		);
	}

	/**
	 * Process customer
	 *
	 * @param array    $customer
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function processCustomer($customer, $store_id = null) {
		$row = array(
			'email'           => $customer['email'],
			'firstname'       => $customer['firstname'],
			'lastname'        => $customer['lastname'],
			'phone'           => $this->cleanPhone($customer['telephone'])
		);

		if (!$this->config->isSendTelephone($store_id)) {
			unset($row['phone']);
		}

		$this->event->trigger('newsman/export_retriever_send_subscribers_process_customer/after', array(&$row, $customer, $store_id));

		return $row;
	}

	/**
	 * Process subscriber
	 *
	 * @param array    $subscriber Subscriber.
	 * @param null|int $store_id Store ID.
	 *
	 * @return array
	 */
	public function processSubscriber($subscriber, $store_id = null) {
		$row = array(
			'email'     => $subscriber['email'],
			'firstname' => $subscriber['firstname'],
			'lastname'  => $subscriber['lastname']
		);

		if ($this->config->isSendTelephone()) {
			$row['phone'] = $subscriber['phone'];
		}

		$row['additional'] = array();
		foreach ($this->additional_attributes as $attribute) {
			if (!empty($subscriber[$attribute])) {
				$row['additional'][$attribute] = $subscriber[$attribute];
			} else {
				$row['additional'][$attribute] = '';
			}
		}

		$this->event->trigger('newsman/export_retriever_send_subscribers_process_subscriber/after', array(&$row, $subscriber, $store_id));

		return $row;
	}

	/**
	 * Get allowed request parameters
	 *
	 * @return array
	 */
	public function getWhereParametersMapping() {
		$return = parent::getWhereParametersMapping();

		$return['subscriber_id'] = $return['customer_id'];
		unset($return['customer_id']);
		$return['subscriber_ids'] = $return['customer_ids'];
		unset($return['customer_ids']);

		return $return;
	}
}
