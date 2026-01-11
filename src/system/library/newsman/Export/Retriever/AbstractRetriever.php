<?php

namespace Newsman\Export\Retriever;

use Newsman\Util\Telephone;

/**
 * Class Export Abstract Retriever
 *
 * @class \Newsman\Export\Retriever\AbstractRetriever
 */
class AbstractRetriever extends \Newsman\Nzmbase {
	/**
	 * @var \ModelLocalisationLanguage
	 */
	protected $localisation_language;

	/**
	 * @var \ModelSettingSetting
	 */
	protected $setting;

	/**
	 * @var \ModelSettingStore
	 */
	protected $store_setting;

	/**
	 * Telephone util
	 *
	 * @var Telephone
	 */
	protected $telephone;

	/**
	 * Cache language ID by store ID
	 *
	 * @var array
	 */
	protected $language_id_cache = array();

	/**
	 * @var array
	 */
	protected $cache_config = array();

	/**
	 * @var array
	 */
	protected $stores_urls = array();

	/**
	 * @var array
	 */
	protected $cache_image_width = array();

	/**
	 * @var array
	 */
	protected $cache_image_height = array();

	/**
	 * @var int
	 */
	protected $image_width;

	/**
	 * @var int
	 */
	protected $image_height;

	/**
	 * Class construct
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->registry->load->model('localisation/language');
		$this->localisation_language = $this->registry->get('model_localisation_language');

		$this->registry->load->model('setting/setting');
		$this->setting = $this->registry->get('model_setting_setting');

		$this->registry->load->model('setting/store');
		$this->store_setting = $this->registry->get('model_setting_store');

		$this->telephone = new Telephone($registry);
	}

	/**
	 * Clean telephone string
	 *
	 * @param string $phone Phone.
	 *
	 * @return string
	 */
	public function cleanPhone($phone) {
		return $this->telephone->clean($phone);
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
		$this->event->trigger('newsman/export_retriever_abstract_process_list_params/before', array(&$data, $store_id));

		$params = $this->processListWhereParameters($data, $store_id);

		$sort_found = false;
		if (isset($data['sort'])) {
			$allowed_sort = $this->getAllowedSortFields();
			if (isset($allowed_sort[$data['sort']])) {
				$params['sort'] = $allowed_sort[$data['sort']];
				$sort_found = true;
			}
		}
		$params['order'] = 'ASC';
		if (isset($data['order']) && strcasecmp($data['order'], 'desc') === 0) {
			$params['order'] = 'DESC';
		}
		if (!$sort_found) {
			unset($params['sort']);
			unset($params['order']);
		}

		if (!isset($data['default_page_size'])) {
			$data['default_page_size'] = 1000;
		}
		$params['start'] = (!empty($data['start']) && $data['start'] > 0) ? (int)$data['start'] : 0;
		$params['limit'] = empty($data['limit']) ? $data['default_page_size'] : (int)$data['limit'];
		$params['default_page_size'] = (int)$data['default_page_size'];

		$this->event->trigger('newsman/export_retriever_abstract_process_list_params/after', array(&$params, $data, $store_id));

		return $params;
	}

	/**
	 * Process list where parameters
	 *
	 * @param array    $data
	 * @param int|null $store_id
	 */
	public function processListWhereParameters($data = array(), $store_id = null) {
		$params = array('filters' => array());

		$operators = array_keys($this->getExpressionsDefinition());
		$expressions = $this->getExpressionsDefinition(false);
		$expressions_quoted = $this->getExpressionsDefinition();

		foreach ($this->getWhereParametersMapping() as $request_name => $definition) {
			if (!isset($data[$request_name])) {
				continue;
			}

			$field_name = $definition['field'];
			if (isset($definition['quote']) && $definition['quote']) {
				$is_quoted = true;
			} else {
				$is_quoted = false;
			}

			if (is_array($data[$request_name]) && !empty(array_intersect(array_keys($data[$request_name]), $operators))) {
				$params['filters'][$field_name] = array();
				foreach ($data[$request_name] as $operator => $value) {
					if (!in_array($operator, $operators, true)) {
						continue;
					}

					if ($is_quoted) {
						$expression = $expressions_quoted[$operator];
					} else {
						$expression = $expressions[$operator];
					}

					$expression = str_replace(':field', $field_name, $expression);

					if ($operator === 'in' || $operator === 'nin') {
						$separator = ($is_quoted) ? "','" : ',';
						$expression = str_replace(
							':value',
							implode($separator, $this->escapeValueForSql($value, $definition['type'])),
							$expression
						);
					} else {
						$expression = str_replace(':value', $this->escapeValueForSql($value, $definition['type']), $expression);
					}

					$params['filters'][$field_name][] = $expression;
				}
			} elseif (is_array($data[$request_name]) && $definition['multiple']) {
				$value = $data[$request_name];
				if (!empty($definition['force_array']) && !is_array($value)) {
					$value = array($data[$request_name]);
				}
				$separator = ($is_quoted) ? "','" : ',';
				$params['filters'][$field_name] = $field_name . ' IN (' .
					implode($separator, $this->escapeValueForSql($value, $definition['type'])) . ')';
			} else {
				$value = $data[$request_name];
				$params['filters'][$field_name] = $field_name . ' = ';
				$params['filters'][$field_name] .= ($is_quoted) ? "'" : '';
				$params['filters'][$field_name] .= $this->escapeValueForSql($value, $definition['type']);
				$params['filters'][$field_name] .= ($is_quoted) ? "'" : '';
			}
		}

		return $params;
	}

	/**
	 * Get allowed request parameters
	 *
	 * @return array
	 */
	public function getWhereParametersMapping() {
		return array();
	}

	/**
	 * Get allowed sort fields
	 *
	 * @return array
	 */
	public function getAllowedSortFields() {
		return array();
	}

	/**
	 * Escape value for SQL
	 *
	 * @param mixed  $value
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function escapeValueForSql($value, $type) {
		if (is_string($value)) {
			if ($type === 'int') {
				$value = (int)$value;
			} elseif ($type === 'string') {
				$value = (string)$value;
			}

			return $this->registry->db->escape($value);
		} elseif (is_numeric($value)) {
			if ($type === 'int') {
				$value = (int)$value;
			} elseif ($type === 'string') {
				$value = (string)$value;
			}

			return $this->registry->db->escape($value);
		} elseif (is_array($value)) {
			$return = array();
			foreach ($value as $item) {
				$return[] = $this->escapeValueForSql($item, $type);
			}

			return $return;
		}

		if ($type === 'int') {
			$value = (int)$value;
		} elseif ($type === 'string') {
			$value = (string)$value;
		}

		return $this->registry->db->escape($value);
	}

	/**
	 * Get SQL conditions expression definition
	 *
	 * @return array
	 */
	public function getExpressionsDefinition($add_quotes = true) {
		if ($add_quotes) {
			$value = "':value'";
		} else {
			$value = ':value';
		}

		return array(
			'eq'      => ":field = " . $value,
			'neq'     => ":field <> " . $value,
			'like'    => ":field LIKE " . $value,
			'nlike'   => ":field NOT LIKE " . $value,
			'in'      => ":field IN(" . $value . ")",
			'nin'     => ":field NOT IN(" . $value . ")",
			'is'      => ":field IS " . $value,
			'notnull' => ":field IS NOT NULL",
			'null'    => ":field IS NULL",
			'gt'      => ":field > " . $value,
			'lt'      => ":field < " . $value,
			'gteq'    => ":field >= " . $value,
			'lteq'    => ":field <= " . $value,
			'from'    => ":field >= " . $value,
			'to'      => ":field <= " . $value
		);
	}

	/**
	 * Get language ID by store ID
	 *
	 * @param null|int $store_id
	 *
	 * @return int
	 */
	public function getLanguageIdByStoreId($store_id = null) {
		if ($store_id === null) {
			$store_id = $this->config->getCurrentStoreId();
		}

		if (isset($this->language_id_cache[$store_id])) {
			return $this->language_id_cache[$store_id];
		}

		$languages = $this->localisation_language->getLanguages();
		$config_data = $this->getConfigCache($store_id);
		$code = '';
		if (isset($config_data['config_language'])) {
			$code = $config_data['config_language'];
		}

		foreach ($languages as $language) {
			if ($language['code'] == $code) {
				$this->language_id_cache[$store_id] = (int)$language['language_id'];

				return $this->language_id_cache[$store_id];
			}
		}

		$this->language_id_cache[$store_id] = (int)$this->registry->get('config')->get('config_language_id');

		return $this->language_id_cache[$store_id];
	}

	/**
	 * Get config store base url
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getConfigStoreBaseUrl($store_id) {
		$this->event->trigger('newsman/export_retriever_products_get_store_url/before', array(&$url, $store_id));

		$key_config = ($this->registry->request->server['HTTPS']) ? 'config_ssl' : 'config_url';
		$key_store = ($this->registry->request->server['HTTPS']) ? 'ssl' : 'url';
		$stores = $this->store_setting->getStores();
		$found = false;
		foreach ($stores as $store) {
			if ($store['store_id'] == $store_id) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			return rtrim($this->registry->config->get($key_config), '/') . '/';
		}

		if (!empty($store[$key_store])) {
			return rtrim($store[$key_store], '/') . '/';
		}

		return rtrim($this->registry->config->get($key_config), '/') . '/';
	}

	/**
	 * Get config cache
	 *
	 * @param int $store_id
	 *
	 * @return array
	 */
	public function getConfigCache($store_id) {
		if (isset($this->cache_config[$store_id])) {
			return $this->cache_config[$store_id];
		}
		$this->cache_config[$store_id] = $this->setting->getSetting('config', $store_id);

		return $this->cache_config[$store_id];
	}

	/**
	 * Get config image width
	 *
	 * @param int $store_id
	 *
	 * @return int
	 */
	public function getConfigImageWidth($store_id) {
		if (isset($this->cache_image_width[$store_id])) {
			return $this->cache_image_width[$store_id];
		}

		$config_data = $this->getConfigCache($store_id);
		$theme = 'default';
		if (isset($config_data['config_theme'])) {
			$theme = $config_data['config_theme'];
		}

		$config_data = $this->setting->getSetting('theme_' . $theme, $store_id);
		if (isset($config_data['theme_' . $theme . '_image_popup_width'])) {
			$this->cache_image_width[$store_id] = (int)$config_data['theme_' . $theme . '_image_popup_width'];

			return $this->cache_image_width[$store_id];
		}

		$this->cache_image_width[$store_id] = (int)$this->registry->config->get('theme_' . $theme . '_image_popup_width');

		return $this->cache_image_width[$store_id];
	}

	/**
	 * Get config image height
	 *
	 * @param int $store_id
	 *
	 * @return int
	 */
	public function getConfigImageHeight($store_id) {
		if (isset($this->cache_image_height[$store_id])) {
			return $this->cache_image_height[$store_id];
		}

		$config_data = $this->getConfigCache($store_id);
		$theme = 'default';
		if (isset($config_data['config_theme'])) {
			$theme = $config_data['config_theme'];
		}

		$config_data = $this->setting->getSetting('theme_' . $theme, $store_id);
		if (isset($config_data['theme_' . $theme . '_image_popup_height'])) {
			$this->cache_image_height[$store_id] = (int)$config_data['theme_' . $theme . '_image_popup_height'];

			return $this->cache_image_height[$store_id];
		}

		$this->cache_image_height[$store_id] = (int)$this->registry->config->get('theme_' . $theme . '_image_popup_height');

		return $this->cache_image_height[$store_id];
	}

	/**
	 * Set image width
	 *
	 * @param int $width
	 * @param int $store_id
	 *
	 * @return $this
	 */
	public function setImageWidth($width, $store_id) {
		$this->event->trigger('newsman/export_retriever_products_set_image_width/before', array(&$width, $store_id));
		$this->image_width = $width;

		return $this;
	}

	/**
	 * Get image width
	 *
	 * @return int
	 */
	public function getImageWidth() {
		return $this->image_width;
	}

	/**
	 * Set image height
	 *
	 * @param int $height
	 * @param int $store_id
	 *
	 * @return $this
	 */
	public function setImageHeight($height, $store_id) {
		$this->event->trigger('newsman/export_retriever_products_set_image_height/before', array(&$height, $store_id));
		$this->image_height = $height;

		return $this;
	}

	/**
	 * Get image height
	 *
	 * @return int
	 */
	public function getImageHeight() {
		return $this->image_height;
	}

	/**
	 * Get config stock checkout
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function getConfigStockCheckout($store_id) {
		$config_data = $this->getConfigCache($store_id);
		if (isset($config_data['config_stock_checkout'])) {
			return (bool)$config_data['config_stock_checkout'];
		}
		$this->cache_image_height[$store_id] = (bool)$this->registry->config->get('config_stock_checkout');

		return $this->cache_image_height[$store_id];
	}

	/**
	 * Get config SEO URL
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function getConfigSeoUrl($store_id) {
		$config_data = $this->getConfigCache($store_id);
		if (isset($config_data['config_seo_url'])) {
			return (bool)$config_data['config_seo_url'];
		}
		$this->cache_image_height[$store_id] = (bool)$this->registry->config->get('config_seo_url');

		return $this->cache_image_height[$store_id];
	}
}
