<?php

namespace Newsman;

/**
 * Newsman Config
 *
 * @property \Config              $config
 * @property \Loader              $load
 * @property \ModelSettingSetting $model_setting_setting
 * @property \ModelSettingStore   $model_setting_store
 * @property \Newsman\Nzmloader   $nzmloader
 */
class Nzmconfig extends Library {
	/**
	 * Newsman namespace
	 */
	public const NAMESPACE_NEWSMAN = 'newsman';

	/**
	 * Module Newsman namespace
	 */
	public const NAMESPACE_MODULE_NEWSMAN = 'module_newsman';

	/**
	 * Newsman Remarketing namespace
	 */
	public const NAMESPACE_REMARKETING = 'analytics_newsmanremarketing';

	/**
	 * Cached store IDs
	 *
	 * @var array
	 */
	protected $cached_store_ids;

	/**
	 * @var array
	 */
	protected $config_loaded = array();

	/**
	 * @var array
	 */
	protected $nzm_config = array();

	/**
	 * @var \Registry
	 */
	protected $registry;

	/**
	 * Constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		// Most classes from Newsman extension use Nzmconfig
		$this->load->library('newsman/nzmloader');
		$this->nzmloader->autoload();

		$this->init();
	}

	/**
	 * Initialize config
	 *
	 * @param bool $force
	 */
	public function init($force = false) {
		$this->load->model('setting/setting');
		$this->load->model('setting/store');

		$store_id = $this->config->get('config_store_id');
		if ($store_id === null) {
			$store_id = 0;
		}
		$store_id = (int)$store_id;

		if (!isset($this->config_loaded[$store_id])) {
			$this->config_loaded[$store_id] = false;
		}

		foreach ($this->getAllStoreIds() as $a_store_id) {
			if (!$force && !empty($this->config_loaded[$a_store_id])) {
				continue;
			}

			if ($a_store_id === $store_id) {
				// Assumes that config is loaded in the registry.
				// Explanation: in getConfigValue we use $this->registry->config->get($key) to get config from cache.
				// Target: it speeds up the storefront.
				$this->config_loaded[$a_store_id] = true;
				continue;
			}

			// Load the other stores with SQL query so we do not run init
			// in edge cases and repeat this many times without intent.
			if (!isset($this->nzm_config[$a_store_id])) {
				$this->nzm_config[$a_store_id] = array();
			}
			$this->nzm_config[$a_store_id] = $this->model_setting_setting->getSetting(self::NAMESPACE_NEWSMAN, $a_store_id);
			$this->nzm_config[$a_store_id] = array_merge(
				$this->model_setting_setting->getSetting(self::NAMESPACE_MODULE_NEWSMAN, $a_store_id),
				$this->nzm_config[$a_store_id]
			);
			$this->nzm_config[$a_store_id] = array_merge(
				$this->model_setting_setting->getSetting(self::NAMESPACE_REMARKETING, $a_store_id),
				$this->nzm_config[$a_store_id]
			);
			$this->config_loaded[$a_store_id] = true;
		}
	}

	/**
	 * Get API user ID
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getUserId($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (string)$this->getConfigValue('newsman_user_id', $store_id);
	}

	/**
	 * Get API segment ID
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getSegmentId($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (string)$this->getConfigValue('newsman_segment', $store_id);
	}

	/**
	 * Is send user IP address
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function isSendUerIp($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('newsman_send_user_ip', $store_id);
	}

	/**
	 * Get server IP address.
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getServerIp($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (string)$this->getConfigValue('newsman_server_ip', $store_id);
	}

	/**
	 * Get API key
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getApiKey($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return $this->getConfigValue('newsman_api_key', $store_id);
	}

	/**
	 * Get API list ID
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getListId($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (string)$this->getConfigValue('newsman_list_id', $store_id);
	}

	/**
	 * Get API URL
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getApiUrl($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		$url = (string)$this->getConfigValue('newsman_api_url', $store_id);
		if (empty($url)) {
			$url = 'https://ssl.newsman.app/api/';
		}

		return $url;
	}

	/**
	 * Get API version
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getApiVersion($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		$version = (string)$this->getConfigValue('newsman_api_version', $store_id);
		if (empty($version)) {
			$version = '1.2';
		}

		return $version;
	}

	/**
	 * Get authenticate token used in export data, requests made by newsman.app.
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getAuthenticateToken($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return $this->getConfigValue('newsman_authenticate_token', $store_id);
	}

	/**
	 * Get log level
	 *
	 * @param int $store_id
	 *
	 * @return int
	 */
	public function getLogSeverity($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		$severity = (int)$this->getConfigValue('newsman_developer_log_severity', $store_id);
		if ($severity <= 0) {
			// Default log level is 400 (ERROR)
			$severity = 400;
		}

		return $severity;
	}

	/**
	 * Get API timeout in seconds
	 *
	 * @param int $store_id
	 *
	 * @return int
	 */
	public function getApiTimeout($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();
		$timeout = (int)$this->getConfigValue('newsman_developer_api_timeout', $store_id);
		if ($timeout <= 0) {
			$timeout = 10;
		}

		return $timeout;
	}

	/**
	 * Get log clean days
	 *
	 * @param int $store_id
	 *
	 * @return int
	 */
	public function getDeveloperLogCleanDays($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();
		$days = (int)$this->getConfigValue('newsman_developer_log_clean_days', $store_id);
		if ($days <= 0) {
			$days = 60;
		}

		return $days;
	}

	/**
	 * Is send user IP address
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function isDeveloperActiveUserIp($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('newsman_developer_active_user_ip', $store_id);
	}

	/**
	 * Get server IP address.
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getDeveloperUserIp($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();
		if (!$this->isDeveloperActiveUserIp($store_id)) {
			return '';
		}

		return $this->getConfigValue('newsman_developer_user_ip', $store_id);
	}

	/**
	 * Get export request authorize header name
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getExportAuthorizeHeaderName($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (string)$this->getConfigValue('newsman_export_authorize_header_name', $store_id);
	}

	/**
	 * Get export request authorize header key
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getExportAuthorizeHeaderKey($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (string)$this->getConfigValue('newsman_export_authorize_header_key', $store_id);
	}

	/**
	 * Is subscribe email to list double opt-in
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function isNewsletterDoubleOptin($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('newsman_newsletter_double_optin', $store_id);
	}

	/**
	 * Get all available store IDs.
	 * Cache the store IDs in this class instance.
	 *
	 * @return array
	 */
	public function getAllStoreIds() {
		if ($this->cached_store_ids !== null) {
			return $this->cached_store_ids;
		}

		$store_ids = array(0);
		foreach ($this->model_setting_store->getStores() as $store) {
			$store_ids[] = (int)$store['store_id'];
		}
		$this->cached_store_ids = $store_ids;

		return $this->cached_store_ids;
	}

	/**
	 * Get all user IDs from all stores
	 *
	 * @return array
	 */
	public function getAllUserIds() {
		$user_ids = array();
		foreach ($this->getAllStoreIds() as $store_id) {
			$user_ids[] = $this->getUserId($store_id);
		}

		return array_unique($user_ids);
	}

	/**
	 * Are features enabled with API?
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function isEnabledWithApi($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();
		if (!$this->isActive($store_id)) {
			return false;
		}

		if (!$this->hasApiAccess($store_id)) {
			return false;
		}

		if (empty($this->getListId($store_id))) {
			return false;
		}

		return true;
	}

	/**
	 * Are features enabled with API on any store?
	 *
	 * @return bool
	 */
	public function isEnabledWithApiInAny() {
		foreach ($this->getAllStoreIds() as $store_id) {
			if ($this->isEnabledWithApi($store_id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Has user ID and API key
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function hasApiAccess($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return !empty($this->getUserId($store_id)) && !empty($this->getApiKey($store_id));
	}

	/**
	 * Is Newsman extension active?
	 *
	 * @param int $store_id
	 *
	 * @return bool
	 */
	public function isActive($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('module_newsman_status', $store_id);
	}

	/**
	 * Get all store IDs by list ID
	 *
	 * @param int $list_id
	 *
	 * @return array
	 */
	public function getStoreIdsByListId($list_id) {
		if (empty($list_id)) {
			return array();
		}
		$store_ids = array();
		foreach ($this->getAllStoreIds() as $store_id) {
			if ($this->getListId($store_id) === $list_id && $this->isEnabledWithApi($store_id)) {
				$store_ids[] = $store_id;
			}
		}

		return $store_ids;
	}

	/**
	 * Get user IDs by store IDs
	 *
	 * @param array $store_ids
	 *
	 * @return array
	 */
	public function getUserIdsByStoreIds($store_ids) {
		$user_ids = array();
		foreach ($store_ids as $store_id) {
			$user_ids[] = $this->getUserId($store_id);
		}

		return array_unique($user_ids);
	}

	/**
	 * Get all list IDs
	 *
	 * @return array
	 */
	public function getAllListIds() {
		$list_ids = array();
		foreach ($this->getAllStoreIds() as $store_id) {
			$list_ids[] = $this->getListId($store_id);
		}
		$list_ids = array_unique($list_ids);

		$return = array();
		foreach ($list_ids as $list_id) {
			$store_ids = $this->getStoreIdsByListId($list_id);
			if (!empty($store_ids)) {
				$return[] = $list_id;
			}
		}

		return $return;
	}

	/**
	 * Get store IDs by API key
	 *
	 * @param string $api_key API key.
	 *
	 * @return array
	 */
	public function getStoreIdsByApiKey($api_key) {
		$store_ids = array();
		foreach ($this->getAllStoreIds() as $store_id) {
			if ($this->getApiKey($store_id) === $api_key && $this->isEnabledWithApi($store_id)) {
				$store_ids[] = $store_id;
			}
		}

		return $store_ids;
	}

	/**
	 * Is remarketing active
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isRemarketingActive($store_id = null) {
		return $this->isActive($store_id) &&
			$this->useRemarketing($store_id) &&
			!empty($this->getRemarketingId($store_id));
	}

	/**
	 * Is remarketing active in any store
	 *
	 * @return bool
	 */
	public function isRemarketingActiveInAny() {
		foreach ($this->getAllStoreIds() as $store_id) {
			if ($this->isRemarketingActive($store_id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Use remarketing
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function useRemarketing($store_id = null) {
		return (bool)$this->getConfigValue('analytics_newsmanremarketing_status', $store_id);
	}

	/**
	 * Get Newsman remarketing ID
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 */
	public function getRemarketingId($store_id = null) {
		return $this->getConfigValue('analytics_newsmanremarketing_trackingid', $store_id);
	}

	/**
	 * Is remarketing anonymizing the user IP address?
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isAnonymizeIp($store_id = null) {
		return (bool)$this->getConfigValue('analytics_newsmanremarketing_anonymize_ip', $store_id);
	}

	/**
	 * Is send telephone
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isSendTelephone($store_id = null) {
		return (bool)$this->getConfigValue('analytics_newsmanremarketing_send_telephone', $store_id);
	}

	/**
	 * Is theme cart compatibility enabled (default: true).
	 *
	 * When enabled, the cart-tracking script (cart.twig) polls the cart endpoint
	 * and intercepts AJAX/fetch requests to detect cart changes — works in any theme.
	 *
	 * When disabled, a lighter minicart-DOM-based tracker (minicart.twig) is used
	 * instead — only compatible with the OpenCart 3 default theme minicart block.
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isThemeCartCompatibility($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		$value = $this->getConfigValue('analytics_newsmanremarketing_theme_cart_compatibility', $store_id);
		if ($value === null || $value === '') {
			return true;
		}

		return (bool)$value;
	}

	/**
	 * Get order date to export orders created after it, including.
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 * @deprecated No longer configured via admin UI.
	 *
	 */
	public function getOrderDate($store_id = null) {
		$date_string = $this->getConfigValue('analytics_newsmanremarketing_order_date', $store_id);

		if (!(!empty($date_string) && $this->isValidDateFormat($date_string))) {
			$current_date = new \DateTime();
			$current_date->modify('-5 years');
			$date_string = $current_date->format('Y-m-d');
		}

		return $date_string;
	}

	/**
	 * Test if a string is a valid date in YYYY-MM-DD format
	 *
	 * @param string $date_string The date string to validate.
	 *
	 * @return bool True if valid, false otherwise
	 */
	public function isValidDateFormat($date_string) {
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_string)) {
			return false;
		}
		try {
			$date = \DateTime::createFromFormat('Y-m-d', $date_string);
		} catch (\Exception $e) {
			return false;
		}

		return $date && $date->format('Y-m-d') === $date_string;
	}

	/**
	 * Get remarketing script JS code
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 */
	public function getScriptJs($store_id = null) {
		return (string)$this->getConfigValue('analytics_newsmanremarketing_script_js', $store_id);
	}

	/**
	 * Get script URL
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 * @deprecated
	 */
	public function getScriptUrl($store_id = null) {
		return (string)$this->getConfigValue('analytics_newsmanremarketing_tracking_script_url', $store_id);
	}

	/**
	 * Get resources URL
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 * @deprecated
	 */
	public function getResourcesUrl($store_id = null) {
		return (string)$this->getConfigValue('analytics_newsmanremarketing_http_resource_url', $store_id);
	}

	/**
	 * Get tracking URL
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 * @deprecated
	 */
	public function getTrackingUrl($store_id = null) {
		return (string)$this->getConfigValue('analytics_newsman_remarketing_http_tracking_url', $store_id);
	}

	/**
	 * Get tracking JS run function
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 * @deprecated The run function name is now hard-coded to "_nzm.run" at
	 *             every call site. Kept for backward compatibility only.
	 */
	public function getJsTrackRunFunc($store_id = null) {
		$return = (string)$this->getConfigValue('analytics_newsmanremarketing_js_track_run_func', $store_id);
		if (empty($return)) {
			return '_nzm.run';
		}

		return $return;
	}

	/**
	 * Use proxy
	 *
	 * @param null|int $store_id
	 *
	 * @return false
	 * @deprecated No longer used. Remarketing script is fetched from Newsman API.
	 */
	public function useProxy($store_id = null) {
		return false;
	}

	/**
	 * Get required file patterns URL
	 *
	 * @param null|int $store_id
	 *
	 * @return array
	 * @deprecated
	 */
	public function getRequiredFilePatterns($store_id = null) {
		$str = (string)$this->getConfigValue('analytics_newsmanremarketing_http_required_file_patterns', $store_id);
		if (empty($str)) {
			return array();
		}
		$str = str_replace("\r", "\n", $str);
		$str = preg_replace('/\n{2,}/', "\n", $str);
		$arr = explode("\n", $str);
		if (empty($arr)) {
			return array();
		}
		$return = array();
		foreach ($arr as $pattern) {
			if (!empty($pattern)) {
				$return[] = trim($pattern);
			}
		}

		return $return;
	}

	/**
	 * Get script request URL path
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 * @deprecated
	 */
	public function getScriptRequestUriPath($store_id = null) {
		$url = $this->getScriptUrl($store_id);
		if (empty($url)) {
			return '';
		}

		$url_info = parse_url($url);
		if (isset($url_info['path']) && !empty($url_info['path'])) {
			$url_info['path'] = ltrim($url_info['path'], '/');
			if (empty($url_info['path'])) {
				return '';
			}

			return $url_info['path'];
		}

		return '';
	}

	/**
	 * Get Newsman OAuth URL
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 */
	public function getOauthUrl($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();
		$url = $this->getConfigValue('newsman_oauth_url', $store_id);
		if (empty($url)) {
			return 'https://newsman.app/admin/oauth/authorize?response_type=code&client_id=nzmplugin&nzmplugin=Opencart&scope=api&redirect_uri=__redirect_url__';
		}

		return $url;
	}

	/**
	 * Get Newsman OAuth Token URL
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 */
	public function getOautTokenhUrl($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();
		$url = $this->getConfigValue('newsman_oauth_token_url', $store_id);
		if (empty($url)) {
			return 'https://newsman.app/admin/oauth/token';
		}

		return $url;
	}

	/**
	 * Get config value
	 *
	 * @param string $key
	 * @param int    $store_id
	 *
	 * @return mixed|null
	 */
	public function getConfigValue($key, $store_id = null) {
		$current_store_id = $this->getCurrentStoreId();
		$store_id = ($store_id !== null) ? $store_id : $current_store_id;
		if ($current_store_id === $store_id) {
			return $this->registry->config->get($key);
		}

		if ($this->config_loaded[$store_id] && isset($this->nzm_config[$store_id][$key])) {
			return $this->nzm_config[$store_id][$key];
		}

		return null;
	}

	/**
	 * Get current store ID
	 *
	 * @return int
	 */
	public function getCurrentStoreId() {
		$store_id = $this->config->get('config_store_id');
		if ($store_id === null) {
			$store_id = 0;
		}

		return (int)$store_id;
	}

	/**
	 * Get setup version
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	public function getSetupVersion($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (string)$this->getConfigValue('newsman_setup_version', $store_id);
	}

	/**
	 * Is checkout newsletter active
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isCheckoutNewsletter($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('newsman_checkout_newsletter', $store_id);
	}

	/**
	 * Is checkout newsletter checked by default
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isCheckoutNewsletterDefault($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('newsman_checkout_newsletter_default', $store_id);
	}

	/**
	 * Get checkout newsletter label
	 *
	 * @param null|int $store_id
	 *
	 * @return string
	 */
	public function getCheckoutNewsletterLabel($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();
		$label = $this->getConfigValue('newsman_checkout_newsletter_label', $store_id);

		if (empty($label)) {
			return 'I wish to subscribe to the newsletter.';
		}

		return $label;
	}

	/**
	 * Is export subscribers by store active
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isExportSubscribersByStore($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('newsman_export_subscribers_by_store', $store_id);
	}

	/**
	 * Is export customers by store active
	 *
	 * @param null|int $store_id
	 *
	 * @return bool
	 */
	public function isExportCustomersByStore($store_id = null) {
		$store_id = ($store_id !== null) ? $store_id : $this->getCurrentStoreId();

		return (bool)$this->getConfigValue('newsman_export_customers_by_store', $store_id);
	}

	/**
	 * Get storage config
	 *
	 * @return \Config
	 */
	public function getStorageConfig() {
		return $this->registry->config;
	}
}
