<?php

namespace Newsman\Service\Configuration;

use Newsman\Service\AbstractService;
use Newsman\Service\Context\Configuration\User;

/**
 * API Class Service Configuration Get List All
 *
 * @class \Newsman\Service\Configuration\GetListAll
 */
class GetListAll extends AbstractService {
	/**
	 * Get all lists Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/list.all
	 */
	public const ENDPOINT = 'list.all';

	/**
	 * Get all lists by user ID
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
		$this->event->trigger('newsman/service_configuration_get_list_all/before', array($context));
		$result = $client->get($api_context);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		return $result;
	}
}
