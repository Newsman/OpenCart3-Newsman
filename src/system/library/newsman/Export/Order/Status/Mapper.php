<?php

namespace Newsman\Export\Order\Status;

/**
 * Class Export Order Status Mapper
 *
 * @class \Newsman\Export\Order\Mapper
 */
class Mapper extends \Newsman\Nzmbase {
	/**
	 * @var \ModelSettingSetting
	 */
	protected $setting;

	/**
	 * @var array
	 */
	protected static $cache = array();

	/**
	 * @var array
	 */
	protected static $cache_config = array();

	/**
	 * Constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->registry->load->model('setting/setting');
		$this->setting = $this->registry->get('model_setting_setting');
	}

	/**
	 * Map order status
	 *
	 * @param int    $order_status_id
	 * @param string $order_status
	 * @param int    $store_id
	 * @param bool   $is_new
	 *
	 * @return string
	 */
	public function map($order_status_id, $order_status, $store_id, $is_new = false) {
		$order_status_id = (int)$order_status_id;
		$store_id = (int)$store_id;

		if (isset(self::$cache[$store_id][$order_status_id])) {
			return self::$cache[$store_id][$order_status_id];
		}

		if ($is_new && $order_status_id === 0) {
			return 'pending';
		}

		$config_data = $this->getConfigCache($store_id);

		$complete_status = array();
		if (isset($config_data['config_complete_status'])) {
			$complete_status = (array)$config_data['config_complete_status'];
		}

		$processing_status = array();
		if (isset($config_data['config_processing_status'])) {
			$processing_status = (array)$config_data['config_processing_status'];
		}

		$normalized = strtolower((string)$order_status);

		if (in_array($order_status_id, $complete_status)) {
			$normalized = 'complete';
		} elseif (in_array($order_status_id, $processing_status)) {
			$normalized = 'processing';
		}

		if (!isset(self::$cache[$store_id])) {
			self::$cache[$store_id] = array();
		}

		self::$cache[$store_id][$order_status_id] = $normalized;

		return $normalized;
	}

	/**
	 * Get config cache
	 *
	 * @param int $store_id
	 *
	 * @return array
	 */
	public function getConfigCache($store_id) {
		if (isset(self::$cache_config[$store_id])) {
			return self::$cache_config[$store_id];
		}
		self::$cache_config[$store_id] = $this->setting->getSetting('config', $store_id);

		return self::$cache_config[$store_id];
	}
}
