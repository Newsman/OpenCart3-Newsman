<?php

namespace Newsman\Service\Configuration;

use Newsman\Service\AbstractService;

/**
 * API Class Service Configuration set the feed on a list
 *
 * @class \Newsman\Service\Configuration\SetFeedOnList
 */
class SetFeedOnList extends AbstractService {
	/**
	 * Installs a feed via API in Newsman
	 *
	 * @see https://kb.newsman.com/ap/1.2/feeds.setFeedOnList
	 */
	public const ENDPOINT = 'feeds.setFeedOnList';

	/**
	 * Update feed by list ID in Newsman
	 *
	 * @param \Newsman\Service\Context\Configuration\SetFeedOnList $context Set the feed on list context.
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

		$this->logger->info(sprintf($this->escapeHtml('Try to install products feed %s'), $context->getUrl()));

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_configuration_set_feed_on_list/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'   => $api_context->getListId(),
				'url'       => $context->getUrl(),
				'website'   => $context->getWebsite(),
				'type'      => $context->getType(),
				'return_id' => $context->getReturnId(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(sprintf($this->escapeHtml('Installed products feed %s'), $context->getUrl()));

		return $result;
	}
}
