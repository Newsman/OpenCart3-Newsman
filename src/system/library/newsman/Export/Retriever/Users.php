<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Users
 *
 * @class \Newsman\Export\Retriever\Users
 */
class Users extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Default batch page size
	 */
	public const DEFAULT_PAGE_SIZE = 1000;

	/**
	 * Additional product attributes
	 *
	 * @var array
	 */
	protected $additional_attributes = array();

	/**
	 * Is subscribers retriever
	 *
	 * @var bool
	 */
	protected $is_subscribers = false;

	/**
	 * Is customers retriever
	 *
	 * @var bool
	 */
	protected $is_customers = false;

	/**
	 * @return bool
	 */
	public function getIsSubscriber() {
		return $this->is_subscribers;
	}

	/**
	 * @param bool $is_subscribers
	 *
	 * @return $this
	 */
	public function setIsSubscriber($is_subscribers) {
		$this->is_subscribers = (bool)$is_subscribers;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getIsCustomers() {
		return $this->is_customers;
	}

	/**
	 * @param bool $is_customers
	 *
	 * @return $this
	 */
	public function setIsCustomers($is_customers) {
		$this->is_customers = (bool)$is_customers;

		return $this;
	}

	/**
	 * Process users retriever
	 *
	 * @param array    $data
	 * @param null|int $store_id
	 *
	 * @return array
	 * @throws \Exception On errors.
	 */
	public function process($data = array(), $store_id = null) {
		$data['default_page_size'] = self::DEFAULT_PAGE_SIZE;

		$this->event->trigger('newsman/export_retriever_users_process_params/before', array(&$data, $store_id));

		if (isset($data['_internal_is_subscribers'])) {
			$this->setIsSubscriber($data['_internal_is_subscribers']);
			unset($data['_internal_is_subscribers']);
		} elseif (isset($data['_internal_is_customers'])) {
			$this->setIsCustomers($data['_internal_is_customers']);
			unset($data['_internal_is_customers']);
		}

		$parameters = $this->processListParameters($data, $store_id);
		$this->event->trigger('newsman/export_retriever_users_process_params/after', array(&$parameters, $data, $store_id));

		$this->logger->info(
			sprintf(
				'Export %s, store ID %s',
				print_r($parameters, true),
				$store_id
			)
		);

		$customers = $this->getCustomers($parameters, $store_id);
		if (empty($customers)) {
			return array();
		}
		$this->event->trigger('newsman/export_retriever_users_process_fetch/after', array(&$customers, $parameters, $store_id));

		$result = array();
		foreach ($customers as $customer) {
			try {
				$result[] = $this->processCustomer($customer, $store_id);
			} catch (\Exception $e) {
				$this->logger->logException($e);
			}
		}

		$this->logger->info(
			sprintf(
				'Exported %s, store ID %s',
				print_r($parameters, true),
				$store_id
			)
		);

		return $result;
	}

	/**
	 * Get customers
	 *
	 * @param array    $params
	 * @param null|int $store_id
	 * @param bool     $is_count
	 *
	 * @return array|int
	 */
	public function getCustomers($params = array(), $store_id = null, $is_count = false) {
		$sql = "SELECT ";
		if (!$is_count) {
			$sql .= " c.customer_id AS customer_id,
                c.customer_group_id AS customer_group_id,
                c.store_id AS store_id,
                c.firstname AS firstname,
                c.lastname AS lastname,
                c.email AS email,
                c.telephone AS telephone,
                c.newsletter AS newsletter,
                c.status AS status,
                c.ip AS ip,
                c.date_added AS date_added,
                CONCAT(c.firstname, ' ', c.lastname) AS name,
                cgd.name AS customer_group";
		} else {
			$sql .= " COUNT(DISTINCT c.customer_id) AS total";
		}

		$sql .= " FROM " . DB_PREFIX . "customer AS c ";

		$sql .= " LEFT JOIN " . DB_PREFIX . "customer_group_description cgd 
					ON (c.customer_group_id = cgd.customer_group_id)";

		if (!empty($params['filters']['affiliate'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "customer_affiliate AS ca ON (c.customer_id = ca.customer_id)";
		}

		$sql .= " WHERE cgd.language_id = '" . $this->getLanguageIdByStoreId($store_id) . "'";

		if ($this->getIsSubscriber()) {
			if ($this->config->isExportSubscribersByStore($store_id)) {
				$sql .= " AND store_id = " . (int)$store_id;
			}
		} elseif ($this->getIsCustomers()) {
			if ($this->config->isExportCustomersByStore($store_id)) {
				$sql .= " AND store_id = " . (int)$store_id;
			}
		} else {
			$sql .= " AND store_id = " . (int)$store_id;
		}

		$where = array();
		foreach ($params['filters'] as $filter) {
			if (is_array($filter)) {
				$where[] = implode(' AND ', $filter);
			} else {
				$where[] = $filter;
			}
		}

		if (!empty($where)) {
			$sql .= ' AND ' . implode(' AND ', $where);
		}

		if (isset($params['sort']) && isset($params['order'])) {
			$sql .= " ORDER BY " . $params['sort'] . ' ' . $params['order'];
		}

		if (!$is_count) {
			$start = 0;
			if (isset($params['start']) && $params['start'] >= 0) {
				$start = (int)$params['start'];
			}
			$limit = $params['default_page_size'];
			if (isset($params['limit']) && $params['limit'] >= 1) {
				$limit = (int)$params['limit'];
			}
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		/** @var \stdClass $query */
		$query = $this->registry->db->query($sql);

		if ($is_count) {
			return (int)$query->row['total'];
		}

		return $query->rows;
	}

	/**
	 * Get count subscribers
	 *
	 * @param array    $params
	 * @param null|int $store_id
	 *
	 * @return int
	 */
	public function getCountCustomers($params = array(), $store_id = null) {
		return $this->getCustomers($params, $store_id, true);
	}

	/**
	 * Process customer
	 *
	 * @param array    $customer Customer data
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function processCustomer($customer, $store_id = null) {
		throw new \Exception('Not implemented.');
	}

	/**
	 * Process list parameters
	 *
	 * @param array    $data
	 * @param int|null $store_id
	 *
	 * @return array
	 */
	public function processListParameters($data = array(), $store_id = null) {
		if (isset($data['modified_at'])) {
			throw new \Exception('modified_at is not implemented.');
		}

		return parent::processListParameters($data, $store_id);
	}

	/**
	 * Get allowed request parameters
	 *
	 * @return array
	 */
	public function getWhereParametersMapping() {
		$return = array(
			'created_at'        => array(
				'field' => 'c.date_added',
				'quote' => true,
				'type'  => 'string'
			),
			'customer_id'       => array(
				'field' => 'c.customer_id',
				'quote' => false,
				'type'  => 'int'
			),
			'customer_ids'      => array(
				'field'       => 'c.customer_id',
				'quote'       => false,
				'multiple'    => true,
				'force_array' => true,
				'type'        => 'int'
			),
			'email'             => array(
				'field' => 'c.email',
				'quote' => true,
				'type'  => 'string'
			),
			'affiliate'         => array(
				'field' => 'ca.status',
				'quote' => false,
				'type'  => 'int'
			),
			'name'              => array(
				'field' => 'CONCAT(c.firstname, \' \', c.lastname)',
				'quote' => true,
				'type'  => 'string'
			),
			'firstname'         => array(
				'field' => 'c.firstname',
				'quote' => true,
				'type'  => 'string'
			),
			'lastname'          => array(
				'field' => 'c.lastname',
				'quote' => true,
				'type'  => 'string'
			),
			'confirmed'         => array(
				'field' => 'c.newsletter',
				'quote' => false,
				'type'  => 'int'
			),
			'customer_group_id' => array(
				'field' => 'c.customer_group_id',
				'quote' => false,
				'type'  => 'int'
			),
			'status'            => array(
				'field' => 'c.status',
				'quote' => false,
				'type'  => 'int'
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
				'name'           => 'name',
				'email'          => 'c.email',
				'customer_group' => 'customer_group',
				'status'         => 'c.status',
				'ip'             => 'c.ip',
				'created_at'     => 'c.date_added',
				'subscriber_id'  => 'c.customer_id'
			)
		);
	}

	/**
	 * Set additional attributes
	 *
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function setAdditionalAttributes($attributes) {
		$this->additional_attributes = $attributes;

		return $this;
	}

	/**
	 * Get additional attributes
	 *
	 * @return array
	 */
	public function getAdditionalAttributes() {
		$this->event->trigger('newsman/export_retriever_users_additional_attributes/after', array(&$this->additional_attributes));

		return $this->additional_attributes;
	}
}
