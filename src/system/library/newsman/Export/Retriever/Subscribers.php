<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Subscribers
 *
 * @class \Newsman\Export\Retriever\Subscribers
 */
class Subscribers extends Users {
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

		return parent::process($data, $store_id);
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
			'subscriber_id'   => $customer['customer_id'],
			'firstname'       => $customer['firstname'],
			'lastname'        => $customer['lastname'],
			'email'           => $customer['email'],
			'phone'           => $this->cleanPhone($customer['telephone']),
			'ip'              => $customer['ip'],
			'date_subscribed' => $customer['date_added'],
			'confirmed'       => 1
		);

		if (!$this->config->isSendTelephone($store_id)) {
			unset($row['phone']);
		}

		foreach ($this->additional_attributes as $attribute) {
			if (!empty($customer[$attribute])) {
				$row[$attribute] = $customer[$attribute];
			} else {
				$row[$attribute] = '';
			}
		}

		$this->event->trigger('newsman/export_retriever_subscribers_process_customer/after', array(&$row, $customer, $store_id));

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
