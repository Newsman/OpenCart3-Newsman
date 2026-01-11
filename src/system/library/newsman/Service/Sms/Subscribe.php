<?php

namespace Newsman\Service\Sms;

use Newsman\Service\AbstractService;

/**
 * API Class Service Subscribe Telephone Number to SMS List
 *
 * @class \Newsman\Service\Sms\Subscribe
 */
class Subscribe extends AbstractService {
	/**
	 * Subscribe telephone number to SMS list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/sms.saveSubscribe
	 */
	public const ENDPOINT = 'sms.saveSubscribe';

	/**
	 * Subscribe telephone number to SMS list
	 *
	 * @param \Newsman\Service\Context\Sms\Subscribe $context SMS subscribe context.
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
				$this->escapeHtml('Try to subscribe telephone %s'),
				$context->getTelephone()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_sms_subscriber/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'   => $api_context->getListId(),
				'telephone' => $context->getTelephone(),
				'firstname' => $context->getFirstname(),
				'lastname'  => $context->getLastname(),
				'ip'        => $context->getIp(),
				'props'     => empty($context->getProperties()) ? '' : $context->getProperties(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Subscribed telephone %s'),
				$context->getTelephone()
			)
		);

		return $result;
	}
}
