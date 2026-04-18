<?php

/**
 * Class ControllerExtensionAnalyticsNewsmanremarketing
 *
 * @property \Newsman\Nzmconfig            $nzmconfig
 * @property \Newsman\Nzmsetup             $nzmsetup
 * @property \Newsman\Nzmlogger            $nzmlogger
 * @property \ModelSettingSetting          $model_setting_setting
 * @property \ModelExtensionNewsmanSetting $model_extension_newsman_setting
 * @property \Loader                       $load
 * @property \Request                      $request
 * @property \Response                     $response
 * @property \Session                      $session
 * @property \Language                     $language
 * @property \Url                          $url
 * @property \Config                       $config
 * @property \Document                     $document
 * @property \Cart\User                    $user
 * @property \DB                           $db
 * @property \Event                        $event
 */
class ControllerExtensionAnalyticsNewsmanremarketing extends Controller {
	/**
	 * @var int
	 */
	protected $store_id;

	/**
	 * @var string
	 */
	protected $module_name = 'newsmanremarketing';

	/**
	 * @var array
	 */
	protected $error = array();

	/**
	 * @var array
	 */
	protected $location = array(
		'module'      => 'extension/analytics',
		'marketplace' => 'marketplace/extension',
	);

	protected $names = array(
		'token'              => 'user_token',
		'setting'            => 'analytics_newsmanremarketing',
		'action'             => 'action',
		'template_extension' => '',
	);

	/**
	 * @var array
	 */
	protected $field_names = array(
		'status',
		'trackingid',
		'anonymize_ip',
		'send_telephone',
		'theme_cart_compatibility',
	);

	/**
	 * @param \Registry $registry
	 *
	 * @throws \Exception
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->store_id = isset($this->request->get['store_id']) ? (int)$this->request->get['store_id'] : 0;

		$this->load->library('newsman/nzmconfig');
		$this->load->library('newsman/nzmsetup');
		$this->load->library('newsman/nzmlogger');
	}

	protected function breadcrumbs() {
		$this->load->language($this->location['module'] . '/' . $this->module_name);

		$breadcrumbs = array();
		$breadcrumbs[] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->names['token'] . '=' . $this->session->data[$this->names['token']], true),
		);

		$breadcrumbs[] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=analytics', true),
		);

		$breadcrumbs[] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $this->store_id, true),
		);

		return $breadcrumbs;
	}

	public function index() {
		// Upgrade Newsman extension data if necessarily
		$this->nzmsetup->upgrade();

		$this->load->language($this->location['module'] . '/' . $this->module_name);
		$this->load->model('setting/setting');
		$this->load->model('extension/newsman/setting');
		$this->document->setTitle($this->language->get('heading_title'));

		// Initialize $data with values from the settings
		$data = array();
		foreach ($this->field_names as $field) {
			$data[$this->names['setting'] . '_' . $field] = $this->model_setting_setting->getSettingValue($this->names['setting'] . '_' . $field, $this->store_id);
		}

		// Default the new theme cart compatibility setting to enabled when it
		// has never been saved, matching Nzmconfig::isThemeCartCompatibility().
		$tcc_key = $this->names['setting'] . '_theme_cart_compatibility';
		if ($data[$tcc_key] === null || $data[$tcc_key] === '') {
			$data[$tcc_key] = 1;
		}

		// If the form is submitted
		if (strcasecmp($this->request->server['REQUEST_METHOD'], 'POST') == 0 && $this->validate()) {
			$settings = array();
			$settings[$this->names['setting'] . '_register'] = $this->module_name;
			foreach ($this->field_names as $field) {
				$settings[$this->names['setting'] . '_' . $field] = $this->request->post[$this->names['setting'] . '_' . $field];
			}
			$this->model_extension_newsman_setting->editSetting($this->names['setting'], $settings, $this->store_id);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=analytics&store_id=' . $this->store_id, true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$data['breadcrumbs'] = $this->breadcrumbs();
		$data['cancel'] = $this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=analytics', true);

		$this->load->model('setting/store');

		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . ' (' . $this->language->get('text_default') . ')',
			'href'     => $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=0', true),
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
				'href'     => $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $result['store_id'], true),
			);
		}

		$data['store_id'] = $this->store_id;

		$store_info = $this->model_setting_store->getStore($this->store_id);
		if ($store_info) {
			$data['store_name'] = $store_info['name'];
		} else {
			$data['store_name'] = $this->config->get('config_name') . $this->language->get('text_default');
		}

		$data['action'] = $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $this->store_id, true);

		if (strcasecmp($this->request->server['REQUEST_METHOD'], 'POST') == 0) {
			foreach ($this->field_names as $field) {
				$data[$this->names['setting'] . '_' . $field] = $this->request->post[$this->names['setting'] . '_' . $field];
			}
		}

		$data['url_newsman_settings'] = $this->url->link('extension/module/newsman', $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $this->store_id, true);

		$data['is_remarketing_connected'] = false;
		$newsman_user_id = $this->model_setting_setting->getSettingValue('newsman_user_id', $this->store_id);
		$newsman_api_key = $this->model_setting_setting->getSettingValue('newsman_api_key', $this->store_id);
		$newsman_list_id = $this->model_setting_setting->getSettingValue('newsman_list_id', $this->store_id);

		if ($newsman_user_id && $newsman_api_key && $newsman_list_id) {
			$remarketing_response = $this->getRemarketingSettings($newsman_list_id, $newsman_user_id, $newsman_api_key);

			if ($remarketing_response) {
				$remarketing_id = $remarketing_response['site_id'] . '-' . $remarketing_response['list_id'] . '-' .
					$remarketing_response['form_id'] . '-' . $remarketing_response['control_list_hash'];

				if ($remarketing_id === $data[$this->names['setting'] . '_trackingid']) {
					$data['is_remarketing_connected'] = true;
				}
			}
		}

		$data['text_credentials_valid'] = $this->language->get('text_credentials_valid');
		$data['text_credentials_invalid'] = $this->language->get('text_credentials_invalid');
		$data['text_api_status_hint'] = $this->language->get('text_api_status_hint');
		$data['entry_api_status'] = $this->language->get('entry_api_status');
		$data['text_store'] = $this->language->get('text_store');
		$data['text_config_for_store'] = sprintf($this->language->get('text_config_for_store'), $data['store_name'], $this->store_id);

		// Load translations
		if (VERSION < '3') {
			$translation_text = array(
				'heading_title',
				'text_extension',
				'text_success',
				'text_edit',
				'text_signup',
				'text_default',
				'text_status',
				'text_enabled',
				'text_disabled',
				'text_button_save',
				'text_button_cancel',
				'text_credentials_valid',
				'text_credentials_invalid',
				'text_api_status_hint',
				'entry_api_status',
				'entry_tracking',
				'entry_status',
				'entry_anonymize_ip',
				'entry_send_telephone',
				'entry_theme_cart_compatibility',
				'entry_theme_cart_compatibility_help',
				'error_permission',
				'error_code',
			);
			foreach ($translation_text as $text) {
				$data[$text] = $this->language->get($text);
			}
		}
		$data['logo'] = HTTP_SERVER . 'view/image/newsman-logo.png';
		$version = new \Newsman\Util\Version($this->registry);
		$data['newsman_version'] = $version->getVersion();
		$data['text_version'] = $this->language->get('text_version');

		$this->response->setOutput($this->load->view($this->location['module'] . '/' . $this->module_name . $this->names['template_extension'], $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', $this->location['module'] . '/' . $this->module_name)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->request->post[$this->names['setting'] . '_trackingid']) {
			$this->error['warning'] = 'Newsman Remarketing code required';
		}

		return !$this->error;
	}

	/**
	 * API get remarketing settings
	 *
	 * @param string      $list_id List ID.
	 * @param null|string $user_id User ID.
	 * @param null|string $api_key API key.
	 *
	 * @return array|false
	 */
	public function getRemarketingSettings($list_id, $user_id = null, $api_key = null) {
		try {
			if ($user_id === null) {
				$user_id = $this->nzmconfig->getUserId($this->store_id);
			}
			if ($api_key === null) {
				$api_key = $this->nzmconfig->getApiKey($this->store_id);
			}

			$context = new \Newsman\Service\Context\Configuration\EmailList();
			$context->setUserId($user_id)
				->setApiKey($api_key)
				->setListId($list_id);
			$get_settings = new \Newsman\Service\Configuration\Remarketing\GetSettings($this->registry);

			return $get_settings->execute($context);
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);

			return false;
		}
	}
}
