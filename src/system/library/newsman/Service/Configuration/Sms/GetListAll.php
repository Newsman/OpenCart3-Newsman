<?php

namespace Newsman\Service\Configuration\Sms;

use Newsman\Service\AbstractService;
use Newsman\Service\Context\Configuration\User;

/**
 * API Class Service Configuration Get SMS List All
 *
 * @class \Newsman\Service\Configuration\Sms\GetListAll
 */
class GetListAll extends AbstractService {
	/**
	 * Get all SMS lists Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/sms.lists
	 */
	public const ENDPOINT = 'sms.lists';

	/**
	 * Get all SMS lists by user ID
	 *
	 * @param User $context Configuration user context.
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
		$this->event->trigger('newsman/service_configuration_sms_get_list_all/before', array($context));
		$result = $client->get($api_context);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		return $result;
	}
}
