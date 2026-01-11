<?php

/**
 * Class ModelExtensionNewsmanSetting
 */
class ModelExtensionNewsmanSetting extends Model {
	/**
	 * Merge setting rather than overwriting them.
	 *
	 * @param string $code
	 * @param array  $data
	 * @param int    $store_id
	 *
	 * @return void
	 */
	public function editSetting($code, $data, $store_id = 0) {
		foreach ($data as $key => $value) {
			if (substr($key, 0, strlen($code)) == $code) {
				/** @var \stdClass $query */
				$query = $this->db->query("SELECT setting_id FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `key` = '" . $this->db->escape($key) . "'");

				$setting_id = null;
				if ($query->num_rows) {
					$setting_id = $query->row['setting_id'];
				}

				if ($setting_id === null) {
					if (!is_array($value)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
					} else {
						$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
					}
				} else {
					if (!is_array($value)) {
						$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($value) . "', serialized = '0'  WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
					} else {
						$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape(json_encode($value)) . "', serialized = '1' WHERE `code` = '" . $this->db->escape($code) . "' AND `key` = '" . $this->db->escape($key) . "' AND store_id = '" . (int)$store_id . "'");
					}
				}
			}
		}
	}

	/**
	 * Delete setting
	 *
	 * @param string $key
	 * @param int    $store_id
	 *
	 * @return void
	 */
	public function deleteSettingByKey($code, $key, $store_id = 0) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'" . " AND `key` = '" . $this->db->escape($key) . "'");
	}
}
