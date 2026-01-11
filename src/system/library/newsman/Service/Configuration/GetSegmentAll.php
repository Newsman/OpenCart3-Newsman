<?php

namespace Newsman\Service\Configuration;

use Newsman\Service\AbstractService;
use Newsman\Service\Context\Configuration\EmailList;

/**
 * API Class Service Configuration Get Segment All
 *
 * @class \Newsman\Service\Configuration\GetSegmentAll
 */
class GetSegmentAll extends AbstractService {
	/**
	 * Get all segments Newsman API endpoint by list ID
	 *
	 * @see https://kb.newsman.com/ap/1.2/segment.all
	 */
	public const ENDPOINT = 'segment.all';

	/**
	 * Get all segments by list ID
	 *
	 * @param EmailList $context List context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		if (empty($context->getListId())) {
			$e = new \Exception($this->escapeHtml('List ID is required.'));
			$this->logger->error($e);
			throw $e;
		}

		$api_context = $this->createApiContext()
			->setUserId($context->getUserId())
			->setApiKey($context->getApiKey())
			->setEndpoint(self::ENDPOINT);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_configuration_get_segment_all/before', array($context));
		$result = $client->get($api_context, array('list_id' => $context->getListId()));

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		return $result;
	}
}
