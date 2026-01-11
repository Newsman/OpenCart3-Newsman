<?php

namespace Newsman\Service\Configuration;

use Newsman\Service\AbstractService;

/**
 * API Class Service Configuration Update Feed
 *
 * @class \Newsman\Service\Configuration\UpdateFeed
 */
class UpdateFeed extends AbstractService {
	/**
	 * Update a feed in Newsman
	 *
	 * @see https://kb.newsman.com/ap/1.2/feeds.updateFeed
	 */
	public const ENDPOINT = 'feeds.updateFeed';

	/**
	 * Update a feed
	 *
	 * @param \Newsman\Service\Context\Configuration\UpdateFeed $context Update Feed context.
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
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$this->logger->info(sprintf($this->escapeHtml('Try to update feed %s'), $context->getListId()));

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_configuration_update_feed/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id' => $api_context->getListId(),
				'feed_id' => $context->getFeedId(),
				'props'   => $context->getProperties(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(sprintf($this->escapeHtml('Updated the feed %s'), $context->getListId()));

		return $result;
	}
}
