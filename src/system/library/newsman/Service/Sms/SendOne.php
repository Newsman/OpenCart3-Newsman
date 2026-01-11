<?php

namespace Newsman\Service\Sms;

use Newsman\Service\AbstractService;

/**
 * API Class Service SMS Send One
 *
 * @class \Newsman\Service\Sms\SendOne
 */
class SendOne extends AbstractService {
	/**
	 * Send one SMS Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/sms.sendone
	 */
	public const ENDPOINT = 'sms.sendone';

	/**
	 * SMS send one
	 *
	 * @param \Newsman\Service\Context\Sms\SendOne $context Sms send one context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		$api_context = $this->createApiContext()
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$this->logger->info(sprintf($this->escapeHtml('Try to send one SMS to %s'), $context->getTo()));

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_sms_send_one/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id' => $api_context->getListId(),
				'text'    => $context->getText(),
				'to'      => $context->getTo(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(sprintf($this->escapeHtml('Sent SMS to %s'), $context->getTo()));

		return $result;
	}
}
