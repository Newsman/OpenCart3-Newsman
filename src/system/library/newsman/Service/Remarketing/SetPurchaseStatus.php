<?php

namespace Newsman\Service\Remarketing;

use Newsman\Service\AbstractService;

/**
 * API Class Service Remarketing order Set Purchase Status
 *
 * @class Newsman\Service\Remarketing\SetPurchaseStatus
 */
class SetPurchaseStatus extends AbstractService {
	/**
	 * Order set purchase status Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/remarketing.setPurchaseStatus
	 */
	public const ENDPOINT = 'remarketing.setPurchaseStatus';

	/**
	 * Set order purchase status
	 *
	 * @param \Newsman\Service\Context\Remarketing\SetPurchaseStatus $context Order Set Purchase Status context.
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
				$this->escapeHtml('Try to send order %s status %s'),
				$context->getOrderId(),
				$context->getOrderStatus()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_remarketing_set_purchase_status/before', array($context));
		$result = $client->get(
			$api_context,
			array(
				'list_id'  => $api_context->getListId(),
				'order_id' => $context->getOrderId(),
				'status'   => $context->getOrderStatus(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Sent order %s status %s'),
				$context->getOrderId(),
				$context->getOrderStatus()
			)
		);

		return $result;
	}
}
