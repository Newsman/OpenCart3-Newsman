<?php

namespace Newsman;

/**
 * Reported cart storage
 *
 * @property \DB $db
 */
class Nzmreportedcart extends \Newsman\Library {
	/**
	 * @return void
	 */
	public function createTable() {
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "newsman_reported_cart` (
				`customer_id` int(11) NOT NULL,
				`store_id` int(11) NOT NULL DEFAULT '0',
				`cart_hash` varchar(32) NOT NULL,
				`date_modified` datetime NOT NULL,
				PRIMARY KEY (`customer_id`, `store_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
		);
	}

	/**
	 * @param int $customer_id
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function get($customer_id, $store_id = 0) {
		$customer_id = (int)$customer_id;
		if ($customer_id <= 0) {
			return '';
		}

		try {
			$query = $this->db->query(
				"SELECT cart_hash FROM `" . DB_PREFIX . "newsman_reported_cart`"
				. " WHERE customer_id = '" . $customer_id . "'"
				. " AND store_id = '" . (int)$store_id . "'"
			);
		} catch (\Exception $e) {
			return '';
		}

		if (empty($query->num_rows)) {
			return '';
		}

		return (string)$query->row['cart_hash'];
	}

	/**
	 * @param int    $customer_id
	 * @param int    $store_id
	 * @param string $hash
	 *
	 * @return void
	 */
	public function set($customer_id, $store_id, $hash) {
		$customer_id = (int)$customer_id;
		if ($customer_id <= 0) {
			return;
		}
		if (!preg_match('/^[a-f0-9]{32}$/i', (string)$hash)) {
			return;
		}

		try {
			$this->db->query(
				"REPLACE INTO `" . DB_PREFIX . "newsman_reported_cart` SET"
				. " customer_id = '" . $customer_id . "',"
				. " store_id = '" . (int)$store_id . "',"
				. " cart_hash = '" . $this->db->escape(strtolower((string)$hash)) . "',"
				. " date_modified = NOW()"
			);
		} catch (\Exception $e) {
			return;
		}
	}

	/**
	 * @param int    $customer_id
	 * @param int    $store_id
	 * @param string $hash
	 *
	 * @return bool
	 */
	public function isReported($customer_id, $store_id, $hash) {
		if (!preg_match('/^[a-f0-9]{32}$/i', (string)$hash)) {
			return false;
		}

		return $this->get($customer_id, $store_id) === strtolower((string)$hash);
	}
}
