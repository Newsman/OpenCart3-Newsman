<?php

namespace Newsman\Service;

/**
 * API Class Service Init Subscribe to Email List
 *
 * @class \Newsman\Service\InitSubscribeEmail
 */
class InitSubscribeEmail extends AbstractService {
	/**
	 * Init subscribe to the email list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/subscriber.initSubscribe
	 */
	public const ENDPOINT = 'subscriber.initSubscribe';

	/**
	 * Init subscribe email
	 *
	 * @param \Newsman\Service\Context\InitSubscribeEmail $context Init subscribe context.
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
				$this->escapeHtml('Try to init subscribe email %s'),
				$context->getEmail()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_init_subscriber_email/before', array($context));
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
				'options'   => empty($context->getOptions()) ? '' : $context->getOptions(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Init subscribe successful for email %s'),
				$context->getEmail()
			)
		);

		return $result;
	}
}
