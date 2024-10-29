<?php

class ControllerExtensionAnalyticsNewsmanremarketing extends Controller
{
	private $module_name = "newsmanremarketing";
	private $error = array();
	private $location = [
		'module' => 'extension/analytics',
		'marketplace' => 'marketplace/extension'
	];
	private $names = [
		'token' => 'user_token',
		'setting' => 'analytics_newsmanremarketing',
		'action' => 'action',
		'template_extension' => ''
	];

	private function breadcrumbs()
	{
		$this->load->language($this->location['module'] . '/' . $this->module);

		$breadcrumbs = [];
		$breadcrumbs[] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->names['token'] . '=' . $this->session->data[$this->names['token']], true)
		);

		$breadcrumbs[] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link($this->location['module'], $this->names['token'] . '=' . $this->session->data[$this->names['token']], true)
		);

		$store_id = isset($this->request->get['store_id']) ? $this->request->get['store_id'] : null;

		$breadcrumbs[] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $store_id, true)
		);
		return $breadcrumbs;
	}

	public function index()
	{
		$this->load->language($this->location['module'] . '/' . $this->module_name);
		$this->load->model('setting/setting');
		$this->document->setTitle($this->language->get('heading_title'));

		$store_id = isset($this->request->get['store_id']) ? $this->request->get['store_id'] : null;

		// Initialize $data with values from the settings
		$data = [
			$this->names['setting'] . '_status' =>
				$this->model_setting_setting->getSettingValue($this->names['setting'] . '_status', $store_id),
			$this->names['setting'] . '_trackingid' =>
				$this->model_setting_setting->getSettingValue($this->names['setting'] . '_trackingid', $store_id)
		];

		// If form is submitted
		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate())
		{
			$settings = [
				$this->names['setting'] . '_register' => $this->module_name,
				$this->names['setting'] . '_status' => $this->request->post[$this->names['setting'] . '_status'],
				$this->names['setting'] . '_trackingid' => $this->request->post[$this->names['setting'] . '_trackingid']
			];
			$this->model_setting_setting->editSetting($this->names['setting'], $settings, $this->request->get['store_id']);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=analytics', true));
		}

		if (isset($this->error['warning']))
		{
			$data['error_warning'] = $this->error['warning'];
		} else
		{
			$data['error_warning'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// breadcrumbs
		$data['breadcrumbs'] = $this->breadcrumbs();

		// form
		$data[$this->names['action']] = $this->url->link($this->location['module'] . '/' . $this->module_name, $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&store_id=' . $store_id, true);
		$data['cancel'] = $this->url->link($this->location['marketplace'], $this->names['token'] . '=' . $this->session->data[$this->names['token']] . '&type=module', true);

		// check if form submitted. Load settings posted if we reached this point.
		if ($this->request->server['REQUEST_METHOD'] == 'POST')
		{
			$data[$this->names['setting'] . '_status'] = $this->request->post[$this->names['setting'] . '_status'];
			$data[$this->names['setting'] . '_trackingid'] = $this->request->post[$this->names['setting'] . '_trackingid'];
		}

		// Load translations
		if (VERSION < '3')
		{
			foreach ([
				         'text_edit',
				         'text_status',
				         'text_enabled',
				         'text_disabled',
				         'text_button_save',
				         'text_button_cancel',
				         'heading_title'
			         ] as $text)
			{
				$data[$text] = $this->language->get($text);
			}
		}
		$this->response->setOutput($this->load->view($this->location['module'] . '/' . $this->module_name . $this->names['template_extension'], $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', $this->location['module'] . '/' . $this->module_name))
		{
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->request->post[$this->names['setting'] . '_trackingid'])
		{
			$this->error['warning'] = 'Newsman Remarketing code required';
		}

		return !$this->error;
	}
}

?>