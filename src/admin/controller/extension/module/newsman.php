<?php

/**
 * Class ControllerExtensionModuleNewsman
 *
 * @property \Newsman\Nzmconfig            $nzmconfig
 * @property \Newsman\Nzmsetup             $nzmsetup
 * @property \Newsman\Nzmlogger            $nzmlogger
 * @property \ModelSettingSetting          $model_setting_setting
 * @property \ModelSettingStore            $model_setting_store
 * @property \ModelExtensionNewsmanSetting $model_extension_newsman_setting
 * @property \Loader                       $load
 */
class ControllerExtensionModuleNewsman extends Controller {
	/**
	 * @var int
	 */
	protected $store_id;

	/**
	 * @var string
	 */
	protected $module_name = "newsman";

	/**
	 * @var array
	 */
	protected $location = array(
		'module'      => 'extension/module',
		'marketplace' => 'marketplace/extension'
	);

	/**
	 * @var array
	 */
	protected $names = array(
		'token'              => 'user_token',
		'setting'            => 'newsman',
		'action'             => 'action',
		'template_extension' => ''
	);

	protected $field_names = array(
		'user_id',
		'api_key',
		'list_id',
		'segment',
		'newsletter_double_optin',
		'send_user_ip',
		'server_ip',
		'export_authorize_header_name',
		'export_authorize_header_key',
		'developer_log_severity',
		'developer_log_clean_days',
		'developer_api_timeout',
		'developer_active_user_ip',
		'developer_user_ip',
		'checkout_newsletter',
		'checkout_newsletter_default',
		'checkout_newsletter_label'
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

	public function index() {
		$this->nzmsetup->upgrade();

		if ($this->isStartOauth()) {
			$this->response->redirect($this->url->link('extension/module/newsman/step1', 'store_id=' . $this->store_id . '&' . $this->names['token'] . '=' . $this->session->data[$this->names['token']], true));
		}

		$this->editModule();
	}

	/**
	 * Executes the first step of the NewsMAN setup process.
	 *
	 * This method performs the following actions:
	 * - Upgrades the NewsMAN setup.
	 * - Prepares and assigns the OAuth URL to the view data.
	 * - Checks if there is an error related to Step 3 in the request and prepares an error message for the view if necessary.
	 * - Renders the login view for Step 1 with the prepared data.
	 *
	 * Any error related to Step 3 is displayed to the user as part of the rendered view.
	 *
	 * @return void
	 */
	public function step1() {
		$this->nzmsetup->upgrade();
		$this->load->language('extension/module/newsman');

		$data = array();
		$data['heading_title'] = $this->language->get('heading_title');
		$data['breadcrumbs'] = $this->breadcrumbs();
		$data['oauth_url'] = $this->getOauthUrl();

		$data['newsman_user_id'] = $this->model_setting_setting->getSettingValue('newsman_user_id', $this->store_id);
		$data['newsman_api_key'] = $this->model_setting_setting->getSettingValue('newsman_api_key', $this->store_id);
		$data['back'] = $this->url->link('extension/module/newsman', 'store_id=' . $this->store_id . '&' . $this->names['token'] . '=' . $this->session->data[$this->names['token']], true);

		$this->addPageLayout($data);

		$step3_error = isset($this->request->get['step3_error']) ? $this->request->get['step3_error'] : '';
		if (!empty($step3_error)) {
			$data['error'] = $this->language->get('error_step3_save');
		}

		$this->response->setOutput($this->load->view('extension/module/newsman/step1_login', $data));
	}

	/**
	 * Executes the second step of the NewsMAN setup process.
	 *
	 * This method performs the following actions:
	 * - Upgrades the NewsMAN setup.
	 * - Retrieves OAuth-related errors from the request and prepares an error message for the view if necessary.
	 * - Handles and validates the OAuth token from the request.
	 * - Performs a cURL request to retrieve the user's ID and API key from NewsMAN.
	 * - Processes and assigns the list data (excluding SMS lists) to the view.
	 *
	 * If any errors are encountered during the process, an error message and retry button are displayed
	 * by rendering the appropriate view.
	 *
	 * @return void
	 */
	public function step2() {
		$this->nzmsetup->upgrade();
		$this->load->language('extension/module/newsman');
		$this->load->model('extension/newsman/setting');

		$data = array(
			'error'             => '',
			'show_retry_button' => false,
		);
		$data['heading_title'] = $this->language->get('heading_title');
		$data['breadcrumbs'] = $this->breadcrumbs();
		$data['oauth_url'] = $this->getOauthUrl();

		// Get the error from the request. If it's not empty, then create an error message for view.
		$oauth_error = isset($this->request->get['error']) ? $this->request->get['error'] : '';
		if (!empty($oauth_error)) {
			if ($oauth_error === 'access_denied') {
				$data['error'] = $this->language->get('error_access_denied');
			} elseif ($oauth_error === 'missing_lists') {
				$data['error'] = $this->language->get('error_missing_lists');
			} else {
				$data['error'] = 'Unknown error: ' . $oauth_error;
			}
		}

		// If there is an error, show the error message and the retry button.
		if (!empty($oauth_error)) {
			$data['show_retry_button'] = true;
			$this->addPageLayout($data);
			$this->response->setOutput($this->load->view('extension/module/newsman/step2_list', $data));

			return;
		}

		// Get the OAuth token from the request. If empty, show an error message.
		$code = isset($this->request->get['code']) ? $this->request->get['code'] : '';
		if (empty($code)) {
			$data['show_retry_button'] = true;
			$data['error'] = $this->language->get('error_token_missing');
			$this->addPageLayout($data);
			$this->response->setOutput($this->load->view('extension/module/newsman/step2_list', $data));

			return;
		}

		$authenticate_token = $this->generateRandomPassword(32);
		$this->model_extension_newsman_setting->editSetting(
			'newsman',
			array(
				'newsman_authenticate_token' => $authenticate_token,
			),
			$this->store_id
		);

		// Get user ID and API key from NewsMAN.
		$curl_body = array(
			'grant_type'   => 'authorization_code',
			'code'         => $code,
			'client_id'    => 'nzmplugin',
			'redirect_uri' => ''
		);
		$ch = curl_init($this->nzmconfig->getOautTokenhUrl($this->store_id));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_body);
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			$data['show_retry_button'] = true;
			$data['error'] .= ' Response error: ' . curl_error($ch);
		}
		curl_close($ch);

		// Assign lists to view variable.
		if ($response !== false) {
			$response = json_decode($response);
			$data['user_id'] = $response->user_id;
			$data['api_key'] = $response->access_token;

			$data['creds'] = json_encode(
				array(
					'newsman_userid' => $response->user_id,
					'newsman_apikey' => $response->access_token
				)
			);

			foreach ($response->lists_data as $l) {
				if (stripos($l->name, 'SMS:') !== false) {
					continue;
				}
				$email_lists[] = array(
					'id'   => $l->list_id,
					'name' => $l->name
				);
			}
			$data['email_lists'] = $email_lists;
			$data['email_lists_length'] = count($email_lists);
		} else {
			$data['show_retry_button'] = true;
			$data['error'] .= ' Error sending cURL request.';
		}

		$data['action'] = $this->url->link('extension/module/newsman/step3', 'store_id=' . $this->store_id . '&' . $this->names['token'] . '=' . $this->session->data[$this->names['token']], true);
		$this->addPageLayout($data);
		$this->response->setOutput($this->load->view('extension/module/newsman/step2_list', $data));
	}

	/**
	 * Configures the NewsMAN module by saving user credentials and list settings,
	 * enabling the extension, and setting up remarketing and product feed configurations.
	 *
	 * The method receives user credentials (`user_id`, `api_key`, and `list_id`) from
	 * the request and validates that all required fields are present. If validation
	 * fails, it redirects the user back to step 1 with an error message.
	 *
	 * Upon successful validation, the method:
	 * - Saves the configuration settings in the database.
	 * - Enables the NewsMAN module.
	 * - Executes the necessary upgrades for the extension.
	 * - Retrieves and configures remarketing settings using NewsMAN's API.
	 * - Sets up a product feed for the specified list.
	 * - Handles exceptions during feed configuration to ensure resiliency.
	 * - Redirects the user to the main NewsMAN module page upon success.
	 *
	 * @return void
	 * Redirects the user to another page based on success or validation failure.
	 */
	public function step3() {
		$this->nzmsetup->upgrade();
		$this->load->model('setting/setting');
		$this->load->model('setting/store');
		$this->load->model('extension/newsman/setting');

		$user_id = isset($this->request->post['user_id']) ? $this->request->post['user_id'] : '';
		$api_key = isset($this->request->post['api_key']) ? $this->request->post['api_key'] : '';
		$list_id = isset($this->request->post['list_id']) ? $this->request->post['list_id'] : '';
		if (empty($user_id) || empty($api_key) || empty($list_id)) {
			$this->response->redirect($this->url->link('extension/module/newsman/step1', 'store_id=' . $this->store_id . '&' . $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&step3_error=1', true));
		}

		// Set configuration in the settings table.
		$settings = array(
			'newsman_user_id' => $user_id,
			'newsman_api_key' => $api_key,
			'newsman_list_id' => $list_id
		);
		$this->model_extension_newsman_setting->editSetting('newsman', $settings, $this->store_id);
		$this->model_extension_newsman_setting->editSetting('module_newsman', array('module_newsman_status' => 1), $this->store_id);
		$this->config->set('module_newsman_status', 1);

		// Upgrade extension after the module is enabled.
		// This is the actual execution of the upgrade on the first installation.
		$this->nzmsetup->upgrade();

		// API get and save in admin remarketing configuration.
		$remarketing_response = $this->getRemarketingSettings($list_id, $user_id, $api_key);
		$remarketing_id = $remarketing_response['site_id'] . '-' . $remarketing_response['list_id'] . '-' .
			$remarketing_response['form_id'] . '-' . $remarketing_response['control_list_hash'];
		$settings = [
			'analytics_newsmanremarketing_register'   => 'newsmanremarketing',
			'analytics_newsmanremarketing_trackingid' => $remarketing_id,
			'analytics_newsmanremarketing_status'     => 1
		];
		$this->model_extension_newsman_setting->editSetting('analytics_newsmanremarketing', $settings, $this->store_id);

		// Install the product feed in Newsman.
		$url = $this->getStorefrontUrl() . "/index.php?route=extension/module/newsman&newsman=products.json&nzmhash=" . $api_key;
		$result = $this->setFeedOnList(
			$list_id,
			$url,
			$this->getStorefrontUrl(),
			'NewsMAN',
			true,
		);
		if (is_array($result) && !empty($result['feed_id'])) {
			$auth_name = $this->generateRandomHeaderName();
			$auth_value = $this->generateRandomPassword();
			$result = $this->updateFeedAuthorize(
				$list_id,
				$result['feed_id'],
				$auth_name,
				$auth_value
			);

			if ($result !== false) {
				$this->model_extension_newsman_setting->editSetting(
					'newsman',
					array(
						'newsman_export_authorize_header_name' => $auth_name,
						'newsman_export_authorize_header_key'  => $auth_value,
					),
					$this->store_id
				);
			}
		}

		$this->response->redirect($this->url->link('extension/module/newsman', 'store_id=' . $this->store_id . '&' . $this->names['token'] . '=' . $this->session->data[$this->names['token']], true));
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

	/**
	 * Call API set feed on a list
	 *
	 * @param string $list_id List ID.
	 * @param string $url URL of feed.
	 * @param string $website Website URL.
	 * @param string $type Type of the feed.
	 * @param bool   $return_id Is return the ID of the feed.
	 *
	 * @return array|false
	 */
	public function setFeedOnList($list_id, $url, $website, $type = 'fixed', $return_id = false) {
		try {
			if ($list_id === null) {
				$list_id = $this->nzmconfig->getListId($this->store_id);
			}

			$context = new \Newsman\Service\Context\Configuration\SetFeedOnList();
			$context->setListId($list_id)
				->setUrl($url)
				->setWebsite($website)
				->setType($type)
				->setReturnId($return_id);
			$this->event->trigger('newsman/step3_set_feed_on_list/before', array($context));

			$set_feed = new \Newsman\Service\Configuration\SetFeedOnList($this->registry);
			$result = $set_feed->execute($context);

			$this->event->trigger('newsman/step3_set_feed_on_list/after', array(&$result));

			return $result;
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);

			return false;
		}
	}

	/**
	 * Call API update feed and set authorize header name and secret
	 *
	 * @param string $list_id List ID.
	 * @param string $feed_id Feed ID.
	 * @param string $auth_name Authorize the header name.
	 * @param string $auth_value Authorize header value.
	 *
	 * @return false|string|array
	 */
	protected function updateFeedAuthorize($list_id, $feed_id, $auth_name, $auth_value) {
		try {
			if ($list_id === null) {
				$list_id = $this->nzmconfig->getListId($this->store_id);
			}

			$properties = array(
				'auth_header_name'  => $auth_name,
				'auth_header_value' => $auth_value,
			);

			$context = new \Newsman\Service\Context\Configuration\UpdateFeed();
			$context->setListId($list_id)
				->setFeedId($feed_id)
				->setProperties($properties);
			$set_feed = new \Newsman\Service\Configuration\UpdateFeed($this->registry);

			return $set_feed->execute($context);
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);

			return false;
		}
	}

	/**
	 * Generates a random string containing lowercase letters (a-z) and hyphens (-).
	 * Suitable for use as an HTTP header name.
	 *
	 * @param int $length The length of the random string to generate. Default is 16.
	 * @param int $recursion_depth Tracks recursion depth to prevent infinite loops. Don't set manually.
	 *
	 * @return string The randomly generated string.
	 */
	protected function generateRandomHeaderName($length = 16, $recursion_depth = 0) {
		// Prevent infinite recursion - limit to 3 levels.
		if ($recursion_depth > 3) {
			$characters = 'abcdefghijklmnopqrstuvwxyz';

			return substr(str_shuffle($characters), 0, $length);
		}

		$characters = 'abcdefghijklmnopqrstuvwxyz-';
		$characters_length = strlen($characters);
		$random_string = '';

		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[random_int(0, $characters_length - 1)];
		}

		// Ensure the string doesn't start or end with a hyphen and doesn't have consecutive hyphens.
		$random_string = ltrim($random_string, '-');
		$random_string = rtrim($random_string, '-');
		$random_string = preg_replace('/-{2,}/', '-', $random_string);

		// If after cleanup the string is too short, append some random letters.
		if (strlen($random_string) < $length / 2) {
			$additional = $this->generateRandomHeaderName(
				$length - strlen($random_string),
				$recursion_depth + 1
			);
			$random_string .= $additional;
		}

		return $random_string;
	}

	/**
	 * Generates a random password consisting of uppercase letters, lowercase letters, and numbers.
	 *
	 * @param int $length The length of the password to generate. Default is 16.
	 *
	 * @return string The randomly generated password.
	 */
	protected function generateRandomPassword($length = 16) {
		$lowercase = 'abcdefghijklmnopqrstuvwxyz';
		$uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$numbers = '0123456789';

		// Combine all characters.
		$all_chars = $lowercase . $uppercase . $numbers;
		$chars_length = strlen($all_chars);

		$password = '';
		for ($i = 0; $i < $length; $i++) {
			$password .= $all_chars[random_int(0, $chars_length - 1)];
		}

		// Ensure password has at least one character from each required set.
		$has_lowercase = preg_match('/[a-z]/', $password);
		$has_uppercase = preg_match('/[A-Z]/', $password);
		$has_number = preg_match('/[0-9]/', $password);

		// Replace characters if any required type is missing.
		if (!$has_lowercase) {
			$password[random_int(0, $length - 1)] = $lowercase[random_int(0, strlen($lowercase) - 1)];
		}

		if (!$has_uppercase) {
			$password[random_int(0, $length - 1)] = $uppercase[random_int(0, strlen($uppercase) - 1)];
		}

		if (!$has_number) {
			$password[random_int(0, $length - 1)] = $numbers[random_int(0, strlen($numbers) - 1)];
		}

		return $password;
	}

	/**
	 * @return bool
	 */
	public function isStartOauth() {
		$setting = $this->model_setting_setting->getSetting('newsman', $this->store_id);
		if (empty($setting["newsman_user_id"]) || empty($setting["newsman_api_key"])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return string
	 */
	public function getOauthUrl() {
		$redirect_uri = $this->url->link('extension/module/newsman/step2', 'store_id=' . $this->store_id . '&' . $this->names['token'] . '=' . $this->session->data[$this->names['token']], true);
		$redirect_uri = str_replace('amp%3B', '', urlencode($redirect_uri));

		return str_replace('__redirect_url__', $redirect_uri, $this->nzmconfig->getOauthUrl($this->store_id));
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	protected function addPageLayout(&$data) {
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
	}

	protected function breadcrumbs() {
		$breadcrumbs = array();
		$breadcrumbs[] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->names['token'] . '=' . $this->session->data[$this->names['token']], true)
		);

		$breadcrumbs[] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=module', true)
		);

		$breadcrumbs[] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $this->store_id, true)
		);

		return $breadcrumbs;
	}

	/**
	 * Edit settings
	 *
	 * @return void
	 */
	public function editModule() {
		$this->load->model('setting/setting');
		$this->load->model('extension/newsman/setting');
		$this->load->language($this->location['module'] . '/' . $this->module_name);
		$this->document->setTitle($this->language->get('heading_title'));

		$data = array(
			'breadcrumbs' => $this->breadcrumbs(),
			'cancel'      => $this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=module', true)
		);

		// Check for messages from other actions
		if (!empty($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}

		if (!empty($this->session->data['error'])) {
			$data['error'] = $this->session->data['error'];
			unset($this->session->data['error']);
		}

		if (!$this->user->hasPermission('modify', $this->location['module'] . '/' . $this->module_name)) {
			$data['warning'] = $this->language->get('error_permission');
		}

		$this->addPageLayout($data);

		// Initialize $data with values from the settings
		foreach ($this->field_names as $field) {
			$data[$this->names['setting'] . '_' . $field] = $this->model_setting_setting->getSettingValue($this->names['setting'] . '_' . $field, $this->store_id);
		}

		$data['module_newsman_status'] = $this->model_setting_setting->getSettingValue('module_newsman_status', $this->store_id);

		// If the form is submitted
		if (strcasecmp($this->request->server['REQUEST_METHOD'], 'POST') == 0 && $this->validate()) {
			$settings = array();
			foreach ($this->field_names as $field) {
				$settings[$this->names['setting'] . '_' . $field] = $this->request->post[$this->names['setting'] . '_' . $field];
			}
			$settings_status = array(
				'module_newsman_status' => $this->request->post['module_newsman_status']
			);

			$this->model_extension_newsman_setting->editSetting($this->names['setting'], $settings, $this->store_id);
			$this->model_extension_newsman_setting->editSetting('module_newsman', $settings_status, $this->store_id);

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=module', true));
		}

		if (strcasecmp($this->request->server['REQUEST_METHOD'], 'POST') == 0) {
			foreach ($this->field_names as $field) {
				$data[$this->names['setting'] . '_' . $field] = $this->request->post[$this->names['setting'] . '_' . $field];
			}
			$data['module_newsman_status'] = $this->request->post['module_newsman_status'];
		}

		$data['developer_log_severity_options'] = array();
		foreach ($this->nzmlogger->getCodes() as $code => $type) {
			$data['developer_log_severity_options'][] = array(
				'code' => $code,
				'type' => $type
			);
		}

		$data['is_connected'] = false;
		$data['list_options'] = array();
		$list_data = $this->getAllLists($data['newsman_user_id'], $data['newsman_api_key']);
		if ($list_data !== false) {
			$data['is_connected'] = true;
			$data['list_options'] = $list_data;
		}

		$data['segment_options'] = array();
		if ($data['newsman_list_id'] > 0) {
			$segment_data = $this->getAllSegmentsByList($data['newsman_user_id'], $data['newsman_api_key'], $data['newsman_list_id']);
			if ($segment_data !== false) {
				$data['segment_options'] = $segment_data;
			}
		}

		$data['url_remarketing_settings'] = $this->url->link('extension/analytics/newsmanremarketing', $this->names['token'] . '=' . $this->session->data[$this->names['token']], true);
		$data['reconfigure'] = $this->url->link('extension/module/newsman/step1', 'store_id=' . $this->store_id . '&' . $this->names['token'] . '=' . $this->session->data[$this->names['token']], true);

		if (VERSION < '3') {
			$translation_text = array(
				'heading_title',
				'heading_title_main',
				'text_module',
				'text_extension',
				'text_header_edit',
				'text_header_developer_edit',
				'text_close',
				'text_success',
				'text_please_select_list',
				'text_please_select_segment',
				'text_credentials_valid',
				'text_credentials_invalid',
				'text_export_authorize_header_name_hint',
				'text_export_authorize_header_key_hint',
				'text_export_authorize_header_name_help',
				'text_export_authorize_header_key_help',
				'text_api_status_hint',
				'text_remarketing_settings',
				'text_cron',
				'text_reconfigure',
				'entry_api_status',
				'entry_module_status',
				'entry_user_id',
				'entry_api_key',
				'entry_list_id',
				'entry_segment',
				'entry_newsletter_double_optin',
				'entry_send_user_ip',
				'entry_server_ip',
				'entry_export_authorize_header_name',
				'entry_export_authorize_header_key',
				'entry_developer_log_severity',
				'entry_developer_log_clean_days',
				'entry_developer_api_timeout',
				'entry_developer_active_user_ip',
				'entry_developer_user_ip',
				'entry_send_user_ip_help',
				'entry_server_ip_help',
				'entry_developer_active_user_ip_help',
				'button_export_subscribers',
				'button_export_orders',
				'button_export_orders_60_days',
				'button_reconfigure',
				'error_permission'
			);
			foreach ($translation_text as $text) {
				$data[$text] = $this->language->get($text);
			}
		}

		$data['export_subscribers'] = $this->url->link('extension/module/newsman/exportsubscribers', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->store_id, true);
		$data['export_orders'] = $this->url->link('extension/module/newsman/exportorders', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->store_id, true);
		$data['export_orders_60_days'] = $this->url->link('extension/module/newsman/exportorders', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->store_id . '&last-days=60', true);

		$this->response->setOutput($this->load->view('extension/module/newsman', $data));
	}

	/**
	 * @param string $user_id
	 * @param string $api_key
	 *
	 * @return array|false
	 */
	public function getAllLists($user_id, $api_key) {
		$return = array();
		try {
			$context = new \Newsman\Service\Context\Configuration\User();
			$context->setStoreId($this->store_id)
				->setUserId($user_id)
				->setApiKey($api_key);
			$get_lists = new \Newsman\Service\Configuration\GetListAll($this->registry);
			$list_data = $get_lists->execute($context);
			foreach ($list_data as $list_item) {
				if ($list_item['list_type'] == 'sms') {
					continue;
				}
				$return[] = $list_item;
			}
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);

			return false;
		}

		return $return;
	}

	/**
	 * @param string $user_id
	 * @param string $api_key
	 * @param string $list_id
	 *
	 * @return array|false
	 */
	public function getAllSegmentsByList($user_id, $api_key, $list_id) {
		try {
			$context = new \Newsman\Service\Context\Configuration\EmailList();
			$context->setStoreId($this->store_id)
				->setUserId($user_id)
				->setApiKey($api_key)
				->setListId($list_id);
			$get_segments = new \Newsman\Service\Configuration\GetSegmentAll($this->registry);
			$return = $get_segments->execute($context);
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);

			return false;
		}

		return $return;
	}

	public function validate() {
		if (!$this->user->hasPermission('modify', $this->location['module'] . '/' . $this->module_name)) {
			return false;
		}

		return true;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	protected function getStorefrontUrl() {
		if ($this->store_id == 0) {
			return $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG;
		}

		$this->load->model('setting/store');
		$store_info = $this->model_setting_store->getStore($this->store_id);

		if ($store_info) {
			return $this->config->get('config_secure') ? $store_info['ssl'] : $store_info['url'];
		}

		return $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG;
	}

	public function install() {
		$this->nzmsetup->install();
	}

	public function uninstall() {
		$this->nzmsetup->uninstall();
	}

	/**
	 * Exports subscribers from the store to the NewsMAN platform.
	 *
	 * @return void
	 */
	public function exportsubscribers() {
		if (!$this->validate()) {
			$this->load->language('extension/module/newsman');
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/module/newsman', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->store_id, true));
		}

		$this->load->language('extension/module/newsman');

		try {
			$cron = new \Newsman\Export\Retriever\CronSubscribers($this->registry);

			$results = $cron->process(array(), $this->store_id);

			$messages = array();
			foreach ($results as $result) {
				if (isset($result['status'])) {
					$messages[] = $result['status'];
				}
			}

			if (!empty($messages)) {
				$this->session->data['success'] = implode(' ', $messages);
			}
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);
			$this->session->data['error'] = $e->getMessage();
		}

		$this->response->redirect($this->url->link('extension/module/newsman', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->store_id, true));
	}

	/**
	 * Exports orders for synchronization with NewsMAN.
	 *
	 * @return void
	 */
	public function exportorders() {
		if (!$this->validate()) {
			$this->load->language('extension/module/newsman');
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/module/newsman', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->store_id, true));
		}

		$this->load->language('extension/module/newsman');

		try {
			$cron = new \Newsman\Export\Retriever\CronOrders($this->registry);

			$data = array(
				'created_at' => array(
					'from' => $this->nzmconfig->getOrderDate($this->store_id)
				)
			);
			$last_days = (isset($this->request->get['last-days'])) ? (int)$this->request->get['last-days'] : false;
			if ($last_days !== false) {
				$data['created_at']['from'] = date('Y-m-d', strtotime('-' . $last_days . ' days'));
			}

			$results = $cron->process($data, $this->store_id);

			$messages = array();
			foreach ($results as $result) {
				if (isset($result['status'])) {
					$messages[] = $result['status'];
				}
			}

			if (!empty($messages)) {
				$this->session->data['success'] = implode(' ', $messages);
			}
		} catch (\Exception $e) {
			$this->nzmlogger->logException($e);
			$this->session->data['error'] = $e->getMessage();
		}

		$this->response->redirect($this->url->link('extension/module/newsman', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->store_id, true));
	}

	/**
	 * Event handler to clean NewsMAN logs.
	 *
	 * @return void
	 */
	public function eventCleanLogs() {
		$clean_log = new \Newsman\Util\CleanLog($this->registry);
		$clean_log->cleanLogs();
	}

	/**
	 * Event handler to upgrade setup.
	 * The event is configured to run on the admin dashboard, list products, and list orders.
	 *
	 * @return void
	 */
	public function eventSetupUpgrade() {
		$this->nzmsetup->upgrade();
	}
}
