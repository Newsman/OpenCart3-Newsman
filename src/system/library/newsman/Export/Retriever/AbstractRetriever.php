<?php

namespace Newsman\Export\Retriever;

use Newsman\Export\V1\ApiV1Exception;
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
	 * @var array
	 */
	protected $cache_stock_checkout = array();

	/**
	 * @var array
	 */
	protected $cache_seo_url = array();

	/**
	 * Class construct
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->registry->get('load')->model('localisation/language');
		$this->localisation_language = $this->registry->get('model_localisation_language');

		$this->registry->get('load')->model('setting/setting');
		$this->setting = $this->registry->get('model_setting_setting');

		$this->registry->get('load')->model('setting/store');
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
			} elseif (isset($data['_v1_filter_fields'])) {
				throw new ApiV1Exception(1008, 'Invalid sort field: ' . $data['sort'], 400);
			}
		}
		$params['order'] = 'ASC';
		if (isset($data['order']) && strcasecmp($data['order'], 'desc') === 0) {
			$params['order'] = 'DESC';
		}
		if (!$sort_found) {
			// Without an explicit ORDER BY, MySQL does not guarantee a stable row
			// order between two LIMIT pages of the same query. Paginated exports
			// then repeat some rows and skip others, so part of the catalog never
			// reaches Newsman. Fall back to the deterministic sort field of the
			// retriever when it defines one.
			$default_sort = $this->getDefaultSortField();
			if (!empty($default_sort)) {
				$params['sort'] = $default_sort;
				$params['order'] = 'ASC';
			} else {
				unset($params['sort']);
				unset($params['order']);
			}
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
		if (!empty($data['_v1_filter_fields'])) {
			$allowed_mapping = $this->getWhereParametersMapping();
			foreach ($data['_v1_filter_fields'] as $field) {
				if (!isset($allowed_mapping[$field])) {
					throw new ApiV1Exception(1006, 'Invalid filter field: ' . $field, 400);
				}
			}
		}

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

			if (is_array($data[$request_name]) && !empty($data[$request_name]) && is_string(array_keys($data[$request_name])[0])) {
				$params['filters'][$field_name] = array();
				foreach ($data[$request_name] as $operator => $value) {
					if (!in_array($operator, $operators, true)) {
						if (isset($data['_v1_filter_fields'])) {
							throw new ApiV1Exception(1007, 'Invalid filter operator: ' . $operator, 400);
						}
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
	 * Get the SQL field used to keep paginated exports deterministic
	 *
	 * Returns an empty string when the retriever builds its own ORDER BY clause
	 * or when it is not paginated.
	 *
	 * @return string
	 */
	public function getDefaultSortField() {
		return '';
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

			return $this->registry->get('db')->escape($value);
		} elseif (is_numeric($value)) {
			if ($type === 'int') {
				$value = (int)$value;
			} elseif ($type === 'string') {
				$value = (string)$value;
			}

			return $this->registry->get('db')->escape($value);
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

		return $this->registry->get('db')->escape($value);
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

		$config_data = $this->getConfigCache($store_id);
		$this->language_id_cache[$store_id] = (int)(isset($config_data['config_language_id']) ? $config_data['config_language_id'] : $this->registry->get('config')->get('config_language_id'));

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

		$key_config = ($this->registry->get('request')->server['HTTPS']) ? 'config_ssl' : 'config_url';
		$key_store = ($this->registry->get('request')->server['HTTPS']) ? 'ssl' : 'url';
		$stores = $this->store_setting->getStores();
		$found = false;
		foreach ($stores as $store) {
			if ($store['store_id'] == $store_id) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			return rtrim($this->registry->get('config')->get($key_config), '/') . '/';
		}

		if (!empty($store[$key_store])) {
			return rtrim($store[$key_store], '/') . '/';
		}

		return rtrim($this->registry->get('config')->get($key_config), '/') . '/';
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
		} else {
			$config_data = $this->getConfigCache($store_id);
			$this->cache_image_width[$store_id] = (int)(isset($config_data['theme_' . $theme . '_image_popup_width']) ? $config_data['theme_' . $theme . '_image_popup_width'] : 0);
		}

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
		} else {
			$config_data = $this->getConfigCache($store_id);
			$this->cache_image_height[$store_id] = (int)(isset($config_data['theme_' . $theme . '_image_popup_height']) ? $config_data['theme_' . $theme . '_image_popup_height'] : 0);
		}

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
	 * Resolve and set the product image dimensions for a store
	 *
	 * The admin "custom feed image size" settings win when enabled and valid;
	 * otherwise the theme popup image size is used (which may be 0 on themes
	 * that do not define it).
	 *
	 * @param int $store_id
	 *
	 * @return $this
	 */
	public function setupImageDimensions($store_id) {
		$width = $this->getConfigImageWidth($store_id);
		$height = $this->getConfigImageHeight($store_id);

		if ($this->config->isFeedImageCustomSize($store_id)) {
			$custom_width = $this->config->getFeedImageWidth($store_id);
			$custom_height = $this->config->getFeedImageHeight($store_id);
			if ($custom_width > 0 && $custom_height > 0) {
				$width = $custom_width;
				$height = $custom_height;
			}
		}

		$this->setImageWidth($width, $store_id);
		$this->setImageHeight($height, $store_id);

		return $this;
	}

	/**
	 * Build a product image URL.
	 *
	 * A resized variant under image/cache/ exists only after something has
	 * actually generated it (the storefront renders it on demand). Guessing its
	 * file name from the configured dimensions produces 404 URLs whenever that
	 * exact variant was never rendered, and produces "-0x0" URLs on themes that
	 * do not use the OpenCart theme_[code]_image_popup_width / _height settings
	 * (for example Journal).
	 *
	 * The URL is resolved in this order:
	 *   1. the original file must exist under DIR_IMAGE, otherwise placeholder;
	 *   2. dimensions come from the admin "custom feed image size" settings when
	 *      enabled, otherwise from the theme popup size; without valid
	 *      dimensions the original image URL is returned;
	 *   3. a resized variant already present on disk is used as-is;
	 *   4. when the admin "generate missing feed images" setting is enabled, the
	 *      variant is generated through the OpenCart "tool/image" model (which
	 *      creates the file and returns its real URL);
	 *   5. otherwise the URL of the original file.
	 *
	 * @param string $image    Product image path, relative to the image dir.
	 * @param int    $store_id
	 *
	 * @return string
	 */
	public function buildImageUrl($image, $store_id) {
		if (empty($image)) {
			return $this->getPlaceholderImageUrl($store_id);
		}

		if (!$this->isExistingImageFile($image)) {
			$this->logger->info(sprintf('Product image file is missing: %s', $image));

			return $this->getPlaceholderImageUrl($store_id);
		}

		$width = (int)$this->getImageWidth();
		$height = (int)$this->getImageHeight();
		$this->event->trigger('newsman/export_retriever_build_image_url/before', array(&$image, &$width, &$height, $store_id));

		if ($width <= 0 || $height <= 0) {
			return $this->stores_urls[$store_id] . 'image/' . $this->encodeImagePath($image);
		}

		$cache_image = $this->getCacheImagePath($image, $width, $height);
		if ($this->isExistingImageFile($cache_image)) {
			return $this->stores_urls[$store_id] . 'image/' . $this->encodeImagePath($cache_image);
		}

		if ($this->config->isFeedImageGenerate($store_id)) {
			$resized_url = $this->resizeImage($image, $width, $height, $store_id);
			if (!empty($resized_url)) {
				return $resized_url;
			}
		}

		return $this->stores_urls[$store_id] . 'image/' . $this->encodeImagePath($image);
	}

	/**
	 * Get the placeholder image URL of a store
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getPlaceholderImageUrl($store_id) {
		return $this->stores_urls[$store_id] . 'image/placeholder.png';
	}

	/**
	 * Check that an image path points to an existing file inside the image dir
	 *
	 * When DIR_IMAGE is not defined the check cannot be performed, so the image
	 * is assumed to exist and the caller keeps its previous behaviour.
	 *
	 * @param string $image Image path, relative to the image dir.
	 *
	 * @return bool
	 */
	public function isExistingImageFile($image) {
		if (!defined('DIR_IMAGE')) {
			return true;
		}

		return is_file(DIR_IMAGE . $image);
	}

	/**
	 * Get the image/cache/ path of a resized variant
	 *
	 * Mirrors the naming used by the OpenCart "tool/image" model, so an
	 * existence check on this path finds exactly the file that model would
	 * create.
	 *
	 * @param string $image  Image path, relative to the image dir.
	 * @param int    $width
	 * @param int    $height
	 *
	 * @return string Path relative to the image dir.
	 */
	public function getCacheImagePath($image, $width, $height) {
		$dot = strrpos($image, '.');
		if ($dot === false) {
			$base = $image;
			$extension = '';
		} else {
			$base = substr($image, 0, $dot);
			$extension = substr($image, $dot + 1);
		}

		return 'cache/' . $base . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;
	}

	/**
	 * Generate the resized variant of an image and return its URL
	 *
	 * Uses the OpenCart "tool/image" model, which writes the file under
	 * image/cache/ when it does not exist yet. The URL returned by the model is
	 * rebased on the store base URL of the export, because the model builds it
	 * from the currently loaded store configuration.
	 *
	 * @param string $image  Image path, relative to the image dir.
	 * @param int    $width
	 * @param int    $height
	 * @param int    $store_id
	 *
	 * @return string Empty string when the resize is not possible.
	 */
	protected function resizeImage($image, $width, $height, $store_id) {
		try {
			$this->registry->get('load')->model('tool/image');
			$model = $this->registry->get('model_tool_image');
			// OpenCart wraps loaded models in a __call-based Proxy, so
			// method_exists() cannot be used here.
			if (!is_object($model) || !is_callable(array($model, 'resize'))) {
				return '';
			}

			$url = $model->resize($image, $width, $height);
			if (empty($url) || !is_string($url)) {
				return '';
			}

			$position = strpos($url, 'image/cache/');
			if ($position === false) {
				return $url;
			}

			return $this->stores_urls[$store_id] . substr($url, $position);
		} catch (\Exception $e) {
			$this->logger->logException($e);

			return '';
		}
	}

	/**
	 * URL encode an image path, segment by segment
	 *
	 * Product image paths may contain spaces or other characters that are not
	 * valid inside a URL. The directory separators must stay unencoded.
	 *
	 * @param string $image Image path, relative to the image dir.
	 *
	 * @return string
	 */
	protected function encodeImagePath($image) {
		$segments = explode('/', str_replace('\\', '/', $image));

		return implode('/', array_map('rawurlencode', $segments));
	}

	/**
	 * Get config stock checkout
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function getConfigStockCheckout($store_id) {
		if (isset($this->cache_stock_checkout[$store_id])) {
			return $this->cache_stock_checkout[$store_id];
		}

		$config_data = $this->getConfigCache($store_id);
		$this->cache_stock_checkout[$store_id] = (bool)(isset($config_data['config_stock_checkout']) ? $config_data['config_stock_checkout'] : false);

		return $this->cache_stock_checkout[$store_id];
	}

	/**
	 * Get config SEO URL
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function getConfigSeoUrl($store_id) {
		if (isset($this->cache_seo_url[$store_id])) {
			return $this->cache_seo_url[$store_id];
		}

		$config_data = $this->getConfigCache($store_id);
		$this->cache_seo_url[$store_id] = (bool)(isset($config_data['config_seo_url']) ? $config_data['config_seo_url'] : false);

		return $this->cache_seo_url[$store_id];
	}
}
