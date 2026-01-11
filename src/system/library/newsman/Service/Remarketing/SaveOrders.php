<?php

namespace Newsman\Service\Remarketing;

use Newsman\Service\AbstractService;

/**
 * API Class Service Remarketing save orders
 *
 * @class Newsman\Service\Remarketing\SaveOrders
 */
class SaveOrders extends AbstractService {
	/**
	 * Orders save order Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/remarketing.saveOrders
	 */
	public const ENDPOINT = 'remarketing.saveOrders';

	/**
	 * Save orders
	 *
	 * @param \Newsman\Service\Context\Remarketing\SaveOrders $context Save orders context.
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
				$this->escapeHtml('Try to %s save orders'),
				count($context->getOrders())
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_remarketing_save_orders/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id' => $api_context->getListId(),
				'orders'  => $context->getOrders(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Saved %s save orders'),
				count($context->getOrders())
			)
		);

		return $result;
	}
}
