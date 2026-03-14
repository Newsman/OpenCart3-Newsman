<?php

namespace Newsman\Export\Retriever;

use Newsman\Export\V1\ApiV1Exception;

/**
 * Handle inbound refresh.remarketing API v1 request.
 *
 * Fetches the remarketing script from the Newsman API via
 * remarketing.getSettings and stores it in oc_setting.
 *
 * @class \Newsman\Export\Retriever\RefreshRemarketing
 */
class RefreshRemarketing extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process refresh remarketing.
	 *
	 * @param array    $data     Request data.
	 * @param null|int $store_id Store ID.
	 *
	 * @return array
	 * @throws ApiV1Exception On validation or execution errors.
	 */
	public function process($data = array(), $store_id = null) {
		$refresh = isset($data['refresh']) ? (int)$data['refresh'] : 0;
		if (1 !== $refresh) {
			throw new ApiV1Exception(9001, 'Missing or invalid "refresh" parameter: must be 1', 400);
		}

		$user_id = $this->config->getUserId($store_id);
		$api_key = $this->config->getApiKey($store_id);
		$list_id = $this->config->getListId($store_id);

		if (empty($user_id) || empty($api_key) || empty($list_id)) {
			throw new ApiV1Exception(9002, 'Plugin is not configured: missing user ID, API key, or list ID', 400);
		}

		try {
			$context = new \Newsman\Service\Context\Configuration\EmailList();
			$context->setUserId($user_id)
				->setApiKey($api_key)
				->setListId($list_id);

			$get_settings = new \Newsman\Service\Configuration\Remarketing\GetSettings($this->registry);
			$settings = $get_settings->execute($context);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			throw new ApiV1Exception(9003, 'Failed to retrieve remarketing settings from Newsman API', 500);
		}

		if (empty($settings) || !is_array($settings) || empty($settings['javascript'])) {
			throw new ApiV1Exception(9004, 'Newsman API returned empty remarketing script', 500);
		}

		$old_remarketing_js = $this->config->getScriptJs($store_id);
		$new_remarketing_js = $settings['javascript'];

		$this->registry->load->model('extension/newsman/setting');
		$setting_model = $this->registry->get('model_extension_newsman_setting');
		$setting_model->editSetting(
			'analytics_newsmanremarketing',
			array('analytics_newsmanremarketing_script_js' => $new_remarketing_js),
			(int)$store_id
		);

		$this->logger->info('refresh.remarketing: updated analytics_newsmanremarketing_script_js, store ' . (int)$store_id);
		$this->logger->warning($new_remarketing_js);

		return array(
			'status'             => 1,
			'old_remarketing_js' => !empty($old_remarketing_js) ? $old_remarketing_js : '',
			'new_remarketing_js' => $new_remarketing_js,
		);
	}
}
