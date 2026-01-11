<?php

namespace Newsman\Service\Remarketing;

use Newsman\Service\AbstractService;

/**
 * API Class Service Remarketing save order
 *
 * @class Newsman\Service\Remarketing\SaveOrder
 */
class SaveOrder extends AbstractService {
	/**
	 * Order save order Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/remarketing.saveOrder
	 */
	public const ENDPOINT = 'remarketing.saveOrder';

	/**
	 * Save order
	 *
	 * @param \Newsman\Service\Context\Remarketing\SaveOrder $context Save order context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		$api_context = $this->createApiContext()
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$details = $context->getOrderDetails();
		$order_id = 'unknown';
		if (is_array($details) && !empty($details['order_no'])) {
			$order_id = $details['order_no'];
		}
		$this->logger->info(
			sprintf(
				$this->escapeHtml('Try to save order %s'),
				$order_id
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_remarketing_save_order/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'        => $api_context->getListId(),
				'order_details'  => $context->getOrderDetails(),
				'order_products' => $context->getOrderProducts(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Saved order %s'),
				$order_id
			)
		);

		return $result;
	}
}
