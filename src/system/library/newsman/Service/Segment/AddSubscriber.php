<?php

namespace Newsman\Service\Segment;

use Newsman\Service\AbstractService;

/**
 * API Class Service Segment add subscriber (email)
 *
 * @class \Newsman\Service\Segment\AddSubscriber
 */
class AddSubscriber extends AbstractService {
	/**
	 * Subscribe telephone number to SMS list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/segment.addSubscriber
	 */
	public const ENDPOINT = 'segment.addSubscriber';

	/**
	 * Add subscriber ID (email) to segment
	 *
	 * @param \Newsman\Service\Context\Segment\AddSubscriber $context Segment add subscriber context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		$api_context = $this->createApiContext()
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Try to add to segment %s subscriber ID %s'),
				$context->getSegmentId(),
				$context->getSubscriberId()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_segment_add_subscriber/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'       => $api_context->getListId(),
				'segment_id'    => $context->getSegmentId(),
				'subscriber_id' => $context->getSubscriberId(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Added to segment %s subscriber ID %s'),
				$context->getSegmentId(),
				$context->getSubscriberId()
			)
		);

		return $result;
	}
}
