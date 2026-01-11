<?php

namespace Newsman\Service;

/**
 * API Class Service Subscribe to Email List
 *
 * @class \Newsman\Service\SubscribeEmail
 */
class SubscribeEmail extends AbstractService {
	/**
	 * Subscribe to email list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/subscriber.saveSubscribe
	 */
	public const ENDPOINT = 'subscriber.saveSubscribe';

	/**
	 * Subscribe email
	 *
	 * @param Context\SubscribeEmail $context Subscribe email context.
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
				$this->escapeHtml('Try to subscribe email %s'),
				$context->getEmail()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_subscribe_email/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'   => $api_context->getListId(),
				'email'     => $context->getEmail(),
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
				$this->escapeHtml('Subscribed email %s'),
				$context->getEmail()
			)
		);

		return $result;
	}
}
