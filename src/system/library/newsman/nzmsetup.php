<?php

namespace Newsman;

/**
 * Setup class
 *
 * @property \Newsman\Nzmconfig            $nzmconfig
 * @property \Loader                       $load
 * @property \ModelExtensionNewsmanSetting $model_extension_newsman_setting
 * @property \ModelSettingEvent            $model_setting_event
 * @property \ModelSettingSetting          $model_setting_setting
 * @property \ModelSettingStore            $model_setting_store
 */
class Nzmsetup extends \Newsman\Library {
	/**
	 * The current version of setup in this file.
	 * The version should or must be in the database after setup execution.
	 *
	 * @var string
	 */
	protected $setup_version = '1.0.2';

	/**
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->load->library('newsman/nzmconfig');
	}

	/**
	 * Install handler
	 *
	 * @return void
	 */
	public function install() {
		$this->load->model('extension/newsman/setting');
		$this->load->model('setting/setting');
		$this->load->model('setting/event');

		$setting['module_newsman_status'] = 1;
		$this->model_extension_newsman_setting->editSetting('module_newsman', $setting);

		$this->setup();
	}

	/**
	 * Upgrade handler
	 *
	 * @return void
	 */
	public function upgrade() {
		$this->load->model('extension/newsman/setting');
		$this->load->model('setting/setting');
		$this->load->model('setting/event');
		$this->load->model('setting/store');

		$update = false;
		foreach ($this->nzmconfig->getAllStoreIds() as $store_id) {
			if ($this->nzmconfig->isActive($store_id)) {
				if (!empty($this->getCurrentVersion($store_id)) && $this->getCurrentVersion($store_id) === $this->setup_version) {
					continue;
				}
				$update = true;
				break;
			}
		}
		if (!$update) {
			return;
		}

		$this->setup();
	}

	/**
	 * Uninstall handler
	 *
	 * @return void
	 */
	public function uninstall() {
		$this->load->model('extension/newsman/setting');
		$this->load->model('setting/setting');
		$this->load->model('setting/event');

		$this->model_setting_setting->deleteSetting('module_newsman');
		$this->model_setting_setting->deleteSetting('newsman');
		$this->model_setting_setting->deleteSetting('analytics_newsmanremarketing');

		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_sale_order');
		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_catalog_product');
		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_dashboard');
		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_module');

		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_sale_order');
		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_catalog_product');
		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_dashboard');
		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_module');

		$this->model_setting_event->deleteEventByCode('newsman_account_newsletter_before');
		$this->model_setting_event->deleteEventByCode('newsman_checkout_order_add_after');
		$this->model_setting_event->deleteEventByCode('newsman_api_order_history_before');
		$this->model_setting_event->deleteEventByCode('newsman_api_order_edit_after');
		$this->model_setting_event->deleteEventByCode('newsman_checkout_register_save_after');
		$this->model_setting_event->deleteEventByCode('newsman_checkout_guest_save_after');
		$this->model_setting_event->deleteEventByCode('newsman_checkout_guest_view_before');
		$this->model_setting_event->deleteEventByCode('newsman_checkout_guest_view_after');

		$this->model_setting_event->deleteEventByCode('newsman_admin_customer_edit_before');
		$this->model_setting_event->deleteEventByCode('newsman_admin_customer_delete_before');
		$this->model_setting_event->deleteEventByCode('newsman_admin_customer_add_after');
		$this->model_setting_event->deleteEventByCode('newsman_account_register_after');
		$this->model_setting_event->deleteEventByCode('newsman_admin_menu');
	}

	/**
	 * Perform setup install or upgrade.
	 *
	 * @return void
	 */
	protected function setup($network_wide = false) {
		foreach ($this->nzmconfig->getAllStoreIds() as $store_id) {
			$this->upgradeEvents($store_id);
			$this->upgradeOptions($store_id);
		}
	}

	/**
	 * Upgrade events.
	 *
	 * @param int $store_id
	 *
	 * @return void
	 */
	protected function upgradeEvents($store_id) {
		$current_version = $this->getCurrentVersion($store_id);

		if (version_compare($current_version, '1.0.0', '<')) {
			$this->upgradeEventsOneZeroZero($store_id);
		}
	}

	/**
	 * Upgrade admin settings.
	 * @note This function should be run last because the newsman_setup_version setting is updated.
	 *
	 * @param int $store_id
	 *
	 * @return void
	 */
	protected function upgradeOptions($store_id) {
		$current_version = $this->getCurrentVersion($store_id);

		if (version_compare($current_version, '1.0.0', '<')) {
			$this->upgradeOptionsOneZeroZero($store_id);
			$this->model_extension_newsman_setting->editSetting(
				'newsman',
				array('newsman_setup_version' => '1.0.0'),
				$store_id
			);
		}

		if (version_compare($current_version, '1.0.1', '<')) {
			$this->upgradeOptionsOneDotZeroDotOne($store_id);
			$this->model_extension_newsman_setting->editSetting(
				'newsman',
				array('newsman_setup_version' => '1.0.1'),
				$store_id
			);
		}

		if (version_compare($current_version, '1.0.2', '<')) {
			$this->upgradeOptionsOneDotZeroDotTwo($store_id);
			$this->model_extension_newsman_setting->editSetting(
				'newsman',
				array('newsman_setup_version' => '1.0.2'),
				$store_id
			);
		}
	}

	/**
	 * Upgrade admin settings 1.0.0
	 *
	 * @param int $store_id
	 *
	 * @return void
	 */
	protected function upgradeOptionsOneZeroZero($store_id) {
		$data = array();
		$data['newsman_api_url'] = 'https://ssl.newsman.app/api/';
		$data['newsman_api_version'] = '1.2';
		$data['newsman_send_user_ip'] = 1;
		$data['newsman_developer_active_user_ip'] = 0;
		$data['newsman_developer_user_ip'] = '';
		$data['newsman_newsletter_double_optin'] = 0;
		$data['newsman_developer_log_severity'] = 400;
		$data['newsman_developer_log_clean_days'] = 60;
		$data['newsman_developer_api_timeout'] = 10;
		$data['newsman_oauth_url'] = 'https://newsman.app/admin/oauth/authorize?response_type=code&client_id=nzmplugin&nzmplugin=Opencart&scope=api&redirect_uri=__redirect_url__';
		$data['newsman_oauth_token_url'] = 'https://newsman.app/admin/oauth/token';
		$data['newsman_checkout_newsletter'] = 1;
		$data['newsman_checkout_newsletter_default'] = 1;
		$data['newsman_checkout_newsletter_label'] = 'I wish to subscribe to the newsletter.';
		$data['newsman_export_subscribers_by_store'] = 0;
		$data['newsman_export_customers_by_store'] = 0;

		$this->model_extension_newsman_setting->editSetting(
			'newsman',
			$data,
			$store_id
		);

		$migrate_settings = array(
			'newsmanuserid'  => 'newsman_user_id',
			'newsmanapikey'  => 'newsman_api_key',
			'newsmanlistid'  => 'newsman_list_id',
			'newsmansegment' => 'newsman_segment',
		);
		foreach ($migrate_settings as $old_setting => $new_setting) {
			$old_value = $this->model_setting_setting->getSettingValue($old_setting, $store_id);
			if (empty($old_value)) {
				$old_value = '';
			}
			$new_value = $this->model_setting_setting->getSettingValue($new_setting, $store_id);
			if (empty($new_value)) {
				$this->model_extension_newsman_setting->editSetting('newsman', array($new_setting => $old_value), $store_id);
				$this->model_extension_newsman_setting->deleteSettingByKey('newsman', $old_setting, $store_id);
			}
		}

		$data = array();
		$data['analytics_newsmanremarketing_status'] = 1;
		$data['analytics_newsmanremarketing_anonymize_ip'] = 0;
		$data['analytics_newsmanremarketing_send_telephone'] = 1;
		$data['analytics_newsmanremarketing_tracking_script_url'] = 'https://t.newsmanapp.com/jt/t.js';
		$data['analytics_newsmanremarketing_http_resource_url'] = 'https://t.newsmanapp.com/';
		$data['analytics_newsman_remarketing_http_tracking_url'] = 'https://rtrack.newsmanapp.com/';

		$data['analytics_newsmanremarketing_http_required_file_patterns'] = 'jt/t.js
jt/nzm_custom_{{api_key}}.js
jt/ecommerce.js
jt/modal_{{api_key}}.js';

		$data['analytics_newsmanremarketing_script_js'] = "var _nzm = _nzm || [],
    _nzm_config = _nzm_config || [];

{{nzmConfigJs}}

(function(w, d, e, u, f, c, l, n, a, m) {
    w[f] = w[f] || [],
    w[c] = w[c] || [],
    a=function(x) {
        return function() {
            w[f].push([x].concat(Array.prototype.slice.call(arguments, 0)));
        }
    },
    m = [\"identify\", \"track\", \"run\"];
    if ({{conditionTunnelScript}}) {
        w[c].js_prefix = '{{resourcesBaseUrl}}';
        w[c].tr_prefix = '{{trackingBaseUrl}}';
    }
    for(var i = 0; i < m.length; i++) {
        w[f][m[i]] = a(m[i]);
    }
    l = d.createElement(e),
    l.async = 1,
    l.src = u,
    l.id=\"nzm-tracker\",
    l.setAttribute(\"data-site-id\", '{{remarketingId}}'),
    n = d.getElementsByTagName(e)[0],
    n.parentNode.insertBefore(l, n);

})(window, document, 'script', '{{trackingScriptUrl}}', '_nzm', '_nzm_config');";

		$data['analytics_newsmanremarketing_js_track_run_func'] = '_nzm.run';

		$current_date = new \DateTime();
		$current_date->modify('-5 years');
		$data['analytics_newsmanremarketing_order_date'] = $current_date->format('Y-m-d');

		$this->model_extension_newsman_setting->editSetting(
			'analytics_newsmanremarketing',
			$data,
			$store_id
		);

		$this->load->model('setting/event');
	}

	/**
	 * Upgrade events 1.0.0
	 *
	 * @param int $store_id
	 *
	 * @return void
	 */
	protected function upgradeEventsOneZeroZero($store_id) {

		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_sale_order');
		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_catalog_product');
		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_dashboard');
		$this->model_setting_event->deleteEventByCode('newsman_upgrade_setup_module');
		$this->model_setting_event->addEvent('newsman_upgrade_setup_sale_order', 'admin/view/sale/order/before', 'extension/module/newsman/eventSetupUpgrade');
		$this->model_setting_event->addEvent('newsman_upgrade_setup_catalog_product', 'admin/view/catalog/product/before', 'extension/module/newsman/eventSetupUpgrade');
		$this->model_setting_event->addEvent('newsman_upgrade_setup_dashboard', 'admin/view/common/dashboard/before', 'extension/module/newsman/eventSetupUpgrade');
		$this->model_setting_event->addEvent('newsman_upgrade_setup_module', 'admin/view/extension/module/newsman/before', 'extension/module/newsman/eventSetupUpgrade');

		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_sale_order');
		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_catalog_product');
		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_dashboard');
		$this->model_setting_event->deleteEventByCode('newsman_clean_logs_module');
		$this->model_setting_event->addEvent('newsman_clean_logs_sale_order', 'admin/view/sale/order/before', 'extension/module/newsman/eventCleanLogs');
		$this->model_setting_event->addEvent('newsman_clean_logs_catalog_product', 'admin/view/catalog/product/before', 'extension/module/newsman/eventCleanLogs');
		$this->model_setting_event->addEvent('newsman_clean_logs_dashboard', 'admin/view/common/dashboard/before', 'extension/module/newsman/eventCleanLogs');
		$this->model_setting_event->addEvent('newsman_clean_logs_module', 'admin/view/extension/module/newsman/before', 'extension/module/newsman/eventCleanLogs');

		$this->model_setting_event->deleteEventByCode('newsman_account_newsletter_before');
		$this->model_setting_event->addEvent('newsman_account_newsletter_before', 'catalog/controller/account/newsletter/before', 'extension/module/newsman/eventAccountNewsletterBefore');

		$this->model_setting_event->deleteEventByCode('newsman_checkout_order_add_after');
		$this->model_setting_event->addEvent('newsman_checkout_order_add_after', 'catalog/model/checkout/order/addOrder/after', 'extension/module/newsman/eventCheckoutOrderAddAfter');

		$this->model_setting_event->deleteEventByCode('newsman_api_order_history_before');
		$this->model_setting_event->addEvent('newsman_api_order_history_before', 'catalog/controller/api/order/history/before', 'extension/module/newsman/eventApiOrderHistoryBefore');

		$this->model_setting_event->deleteEventByCode('newsman_api_order_edit_after');
		$this->model_setting_event->addEvent('newsman_api_order_edit_after', 'catalog/controller/api/order/edit/after', 'extension/module/newsman/eventApiOrderEditAfter');

		$this->model_setting_event->deleteEventByCode('newsman_checkout_register_save_after');
		$this->model_setting_event->addEvent('newsman_checkout_register_save_after', 'catalog/controller/checkout/register/save/after', 'extension/module/newsman/eventCheckoutRegisterSaveAfter');

		$this->model_setting_event->deleteEventByCode('newsman_checkout_guest_save_after');
		$this->model_setting_event->addEvent('newsman_checkout_guest_save_after', 'catalog/controller/checkout/guest/save/after', 'extension/module/newsman/eventCheckoutGuestSaveAfter');

		$this->model_setting_event->deleteEventByCode('newsman_checkout_guest_view_before');
		$this->model_setting_event->addEvent('newsman_checkout_guest_view_before', 'catalog/view/checkout/guest/before', 'extension/module/newsman/eventCheckoutGuestBefore');

		$this->model_setting_event->deleteEventByCode('newsman_checkout_guest_view_after');
		$this->model_setting_event->addEvent('newsman_checkout_guest_view_after', 'catalog/view/checkout/guest/after', 'extension/module/newsman/eventCheckoutGuestAfter');

		$this->model_setting_event->deleteEventByCode('newsman_admin_customer_edit_before');
		$this->model_setting_event->addEvent('newsman_admin_customer_edit_before', 'admin/controller/customer/customer/edit/before', 'extension/module/newsman/eventCustomerEditBefore');

		$this->model_setting_event->deleteEventByCode('newsman_admin_customer_delete_before');
		$this->model_setting_event->addEvent('newsman_admin_customer_delete_before', 'admin/controller/customer/customer/delete/before', 'extension/module/newsman/eventCustomerDeleteBefore');

		$this->model_setting_event->deleteEventByCode('newsman_admin_customer_add_before');
		$this->model_setting_event->addEvent('newsman_admin_customer_add_before', 'admin/controller/customer/customer/add/before', 'extension/module/newsman/eventCustomerAddBefore');

		$this->model_setting_event->deleteEventByCode('newsman_admin_customer_add_after');
		$this->model_setting_event->addEvent('newsman_admin_customer_add_after', 'admin/model/customer/customer/addCustomer/after', 'extension/module/newsman/eventModelCustomerAddAfter');

		$this->model_setting_event->deleteEventByCode('newsman_account_register_after');
		$this->model_setting_event->addEvent('newsman_account_register_after', 'catalog/model/account/customer/addCustomer/after', 'extension/module/newsman/eventAccountRegisterAfter');

		// Admin menu links under Marketing > NewsMAN
		$this->model_setting_event->deleteEventByCode('newsman_admin_menu');
		$this->model_setting_event->addEvent('newsman_admin_menu', 'admin/view/common/column_left/before', 'extension/module/newsman/addAdminLink');
	}

	/**
	 * Upgrade admin settings 1.0.1
	 * Call SaveListIntegrationSetup for stores that have valid credentials configured.
	 *
	 * @param int $store_id
	 *
	 * @return void
	 */
	protected function upgradeOptionsOneDotZeroDotOne($store_id) {
		if (!$this->nzmconfig->hasApiAccess($store_id)) {
			return;
		}

		$list_id = $this->nzmconfig->getListId($store_id);
		if (empty($list_id)) {
			return;
		}

		$authenticate_token = $this->nzmconfig->getAuthenticateToken($store_id);
		if (empty($authenticate_token)) {
			$authenticate_token = $this->generateRandomPassword(32);
			$this->model_extension_newsman_setting->editSetting(
				'newsman',
				array('newsman_authenticate_token' => $authenticate_token),
				$store_id
			);
		}

		$storefront_url = $this->getStorefrontUrl($store_id);

		try {
			$api_url = rtrim($storefront_url, '/') . '/index.php?route=extension/module/newsman';

			$version = new \Newsman\Util\Version($this->registry);
			$payload = array(
				'api_url'                   => $api_url,
				'api_key'                   => $authenticate_token,
				'plugin_version'            => $version->getVersion(),
				'platform_version'          => VERSION,
				'platform_language'         => 'PHP',
				'platform_language_version' => phpversion(),
			);

			$context = new \Newsman\Service\Context\Configuration\SaveListIntegrationSetup();
			$context->setUserId($this->nzmconfig->getUserId($store_id))
				->setApiKey($this->nzmconfig->getApiKey($store_id))
				->setListId($list_id)
				->setIntegration('opencart')
				->setPayload($payload);

			$service = new \Newsman\Service\Configuration\Integration\SaveListIntegrationSetup($this->registry);
			$service->execute($context);
		} catch (\Exception $e) {
			// Do not block the upgrade on API failure.
		}
	}

	/**
	 * Upgrade admin settings 1.0.2
	 * Fetch remarketing script from Newsman API via remarketing.getSettings
	 * and store it in analytics_newsmanremarketing_script_js.
	 *
	 * @param int $store_id
	 *
	 * @return void
	 */
	protected function upgradeOptionsOneDotZeroDotTwo($store_id) {
		if (!$this->nzmconfig->hasApiAccess($store_id)) {
			return;
		}

		$list_id = $this->nzmconfig->getListId($store_id);
		if (empty($list_id)) {
			return;
		}

		try {
			$context = new \Newsman\Service\Context\Configuration\EmailList();
			$context->setUserId($this->nzmconfig->getUserId($store_id))
				->setApiKey($this->nzmconfig->getApiKey($store_id))
				->setListId($list_id);

			$get_settings = new \Newsman\Service\Configuration\Remarketing\GetSettings($this->registry);
			$settings = $get_settings->execute($context);

			if (!empty($settings) && is_array($settings) && !empty($settings['javascript'])) {
				$this->model_extension_newsman_setting->editSetting(
					'analytics_newsmanremarketing',
					array('analytics_newsmanremarketing_script_js' => $settings['javascript']),
					$store_id
				);
			}
		} catch (\Exception $e) {
			// Do not block the upgrade on API failure.
		}
	}

	/**
	 * Get the storefront URL for a given store.
	 *
	 * @param int $store_id
	 *
	 * @return string
	 */
	protected function getStorefrontUrl($store_id) {
		$is_secure = $this->config->get('config_secure');

		if ($store_id == 0) {
			return ($is_secure) ? HTTPS_CATALOG : HTTP_CATALOG;
		}

		$store_info = $this->model_setting_store->getStore($store_id);
		if ($store_info) {
			return ($is_secure) ? $store_info['ssl'] : $store_info['url'];
		}

		return ($is_secure) ? HTTPS_CATALOG : HTTP_CATALOG;
	}

	/**
	 * Generate a random alphanumeric password.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	protected function generateRandomPassword($length = 16) {
		$lowercase = 'abcdefghijklmnopqrstuvwxyz';
		$uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$numbers = '0123456789';
		$all_chars = $lowercase . $uppercase . $numbers;
		$chars_length = strlen($all_chars);

		$password = '';
		for ($i = 0; $i < $length; $i++) {
			$password .= $all_chars[random_int(0, $chars_length - 1)];
		}

		if (!preg_match('/[a-z]/', $password)) {
			$password[random_int(0, $length - 1)] = $lowercase[random_int(0, strlen($lowercase) - 1)];
		}
		if (!preg_match('/[A-Z]/', $password)) {
			$password[random_int(0, $length - 1)] = $uppercase[random_int(0, strlen($uppercase) - 1)];
		}
		if (!preg_match('/[0-9]/', $password)) {
			$password[random_int(0, $length - 1)] = $numbers[random_int(0, strlen($numbers) - 1)];
		}

		return $password;
	}

	/**
	 * Get the current version of setup from the oc_setting table.
	 *
	 * @param int $store_id
	 *
	 * @return false|mixed|null
	 */
	public function getCurrentVersion($store_id) {
		return $this->nzmconfig->getSetupVersion($store_id);
	}
}
