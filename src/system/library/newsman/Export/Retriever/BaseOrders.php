<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Orders
 *
 * @class \Newsman\Export\Retriever\Orders
 */
class BaseOrders extends AbstractRetriever implements RetrieverInterface {
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
		throw new \Exception('Not implemented.');
	}

	/**
	 * Get orders
	 *
	 * @param array    $params
	 * @param null|int $store_id
	 * @param bool     $is_count
	 *
	 * @return array|int
	 */
	public function getOrders($params = array(), $store_id = null, $is_count = false) {
		$sql = "SELECT ";
		if (!$is_count) {
			$sql .= "o.*,(SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . $this->getLanguageIdByStoreId($store_id) . "') AS order_status_name";
		} else {
			$sql .= "COUNT(*) AS total";
		}
		$sql .= " FROM `" . DB_PREFIX . "order` o";

		$where = array();
		if ($store_id !== null) {
			$where[] = "o.store_id = '" . (int)$store_id . "'";
		}

		if (isset($params['where']) && !empty($params['where'])) {
			$where = array_merge($where, $params['where']);
		}

		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		} else {
			$sql .= " WHERE o.order_status_id > '0'";
		}

		if (isset($params['sort']) && isset($params['order'])) {
			$sql .= " ORDER BY " . $params['sort'] . ' ' . $params['order'];
		} else {
			$sql .= " ORDER BY o.order_id DESC";
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
}
