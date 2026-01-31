<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Customers
 *
 * @class \Newsman\Export\Retriever\Customers
 */
class Customers extends Users {
	/**
	 * Process customers retriever
	 *
	 * @param array    $data
	 * @param null|int $store_id
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function process($data = array(), $store_id = null) {
		$data['_internal_is_customers'] = true;

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
			'customer_id'  => $customer['customer_id'],
			'firstname'    => $customer['firstname'],
			'lastname'     => $customer['lastname'],
			'email'        => $customer['email'],
			'phone'        => $this->cleanPhone($customer['telephone']),
			'date_created' => $customer['date_added']
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

		$this->event->trigger('newsman/export_retriever_customers_process_customer/after', array(&$row, $customer, $store_id));

		return $row;
	}
}
