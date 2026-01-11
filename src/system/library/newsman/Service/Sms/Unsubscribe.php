<?php

namespace Newsman\Service\Sms;

use Newsman\Service\AbstractService;

/**
 * API Class Service Unsubscribe Telephone Number from SMS List
 *
 * @class \Newsman\Service\Sms\Unsubscribe
 */
class Unsubscribe extends AbstractService {
	/**
	 * Unsubscribe telephone number from SMS list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/sms.saveUnsubscribe
	 */
	public const ENDPOINT = 'sms.saveUnsubscribe';

	/**
	 * Unsubscribe telephone number from an SMS list
	 *
	 * @param \Newsman\Service\Context\Sms\Unsubscribe $context SMS unsubscribe context.
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
				$this->escapeHtml('Try to unsubscribe telephone %s'),
				$context->getTelephone()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_sms_unsubscribe/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'   => $api_context->getListId(),
				'telephone' => $context->getTelephone(),
				'ip'        => $context->getIp(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Unsubscribed telephone %s'),
				$context->getTelephone()
			)
		);

		return $result;
	}
}
