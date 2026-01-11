<?php

namespace Newsman\Service\Configuration\Remarketing;

use Newsman\Service\AbstractService;
use Newsman\Service\Context\Configuration\EmailList;

/**
 * API Class Service Configuration Remarketing Get Settings
 *
 * @class \Newsman\Service\Configuration\Remarketing\GetSettings
 */
class GetSettings extends AbstractService {
	/**
	 * Get remarketing settings
	 *
	 * @see https://kb.newsman.com/ap/1.2/remarketing.getSettings
	 */
	public const ENDPOINT = 'remarketing.getSettings';

	/**
	 * Get remarketing settings list ID
	 *
	 * @param EmailList $context List context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		$api_context = $this->createApiContext()
			->setUserId($context->getUserId())
			->setApiKey($context->getApiKey())
			->setEndpoint(self::ENDPOINT);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_configuration_remarketing_get_settings/before', array($context));
		$result = $client->get($api_context, array('list_id' => $context->getListId()));

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		return $result;
	}
}
