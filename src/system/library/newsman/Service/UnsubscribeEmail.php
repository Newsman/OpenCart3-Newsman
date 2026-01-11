<?php

namespace Newsman\Service;

/**
 * API Class Service Unsubscribe from Email List
 *
 * @class \Newsman\Service\UnsubscribeEmail
 */
class UnsubscribeEmail extends AbstractService {
	/**
	 * Unsubscribe from email list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/subscriber.saveUnsubscribe
	 */
	public const ENDPOINT = 'subscriber.saveUnsubscribe';

	/**
	 * Unsubscribe email
	 *
	 * @param Context\UnsubscribeEmail $context Unsubscribe email context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		$this->validateEmail($context->getEmail());

		$api_context = $this->createApiContext()
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Try to unsubscribe email %s'),
				$context->getEmail()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_unsubscribe_email/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id' => $api_context->getListId(),
				'email'   => $context->getEmail(),
				'ip'      => $context->getIp(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Unsubscribed email %s'),
				$context->getEmail()
			)
		);

		return $result;
	}
}
