<?php

/**
 * Class ControllerExtensionAnalyticsNewsmanremarketing
 *
 * @property \Newsman\Nzmconfig            $nzmconfig
 * @property \Newsman\Nzmsetup             $nzmsetup
 * @property \ModelSettingSetting          $model_setting_setting
 * @property \ModelExtensionNewsmanSetting $model_extension_newsman_setting
 * @property \Loader                       $load
 */
class ControllerExtensionAnalyticsNewsmanremarketing extends Controller {
	/**
	 * @var int
	 */
	protected $store_id;

	/**
	 * @var string
	 */
	protected $module_name = "newsmanremarketing";

	/**
	 * @var array
	 */
	protected $error = array();

	/**
	 * @var array
	 */
	protected $location = array(
		'module'      => 'extension/analytics',
		'marketplace' => 'marketplace/extension'
	);

	protected $names = array(
		'token'              => 'user_token',
		'setting'            => 'analytics_newsmanremarketing',
		'action'             => 'action',
		'template_extension' => ''
	);

	/**
	 * @var array
	 */
	protected $field_names = array(
		'status',
		'trackingid',
		'anonymize_ip',
		'send_telephone',
		'order_date'
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
	}

	protected function breadcrumbs() {
		$this->load->language($this->location['module'] . '/' . $this->module_name);

		$breadcrumbs = array();
		$breadcrumbs[] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->names['token'] . '=' . $this->session->data[$this->names['token']], true)
		);

		$breadcrumbs[] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=analytics', true)
		);

		$breadcrumbs[] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $this->store_id, true)
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

		// If the form is submitted
		if (strcasecmp($this->request->server['REQUEST_METHOD'], 'POST') == 0 && $this->validate()) {
			$settings = array();
			$settings[$this->names['setting'] . '_register'] = $this->module_name;
			foreach ($this->field_names as $field) {
				$settings[$this->names['setting'] . '_' . $field] = $this->request->post[$this->names['setting'] . '_' . $field];
			}
			$this->model_extension_newsman_setting->editSetting($this->names['setting'], $settings, $this->request->get['store_id']);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=analytics', true));
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
		$data['cancel'] = $this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=module', true);

		if (strcasecmp($this->request->server['REQUEST_METHOD'], 'POST') == 0) {
			foreach ($this->field_names as $field) {
				$data[$this->names['setting'] . '_' . $field] = $this->request->post[$this->names['setting'] . '_' . $field];
			}
		}

		$data['url_newsman_settings'] = $this->url->link('extension/module/newsman', $this->names['token'] . '=' . $this->session->data[$this->names['token']], true);

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
				'entry_tracking',
				'entry_status',
				'entry_anonymize_ip',
				'entry_send_telephone',
				'entry_order_date',
				'error_permission',
				'error_code',
			);
			foreach ($translation_text as $text) {
				$data[$text] = $this->language->get($text);
			}
		}
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
}
