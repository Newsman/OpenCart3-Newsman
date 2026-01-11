<?php

namespace Newsman\Service;

/**
 * API Class Service Init Unsubscribe from Email List
 *
 * @class \Newsman\Service\InitUnsubscribeEmail
 */
class InitUnsubscribeEmail extends AbstractService {
	/**
	 * Init unsubscribe from email list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/subscriber.initUnsubscribe
	 */
	public const ENDPOINT = 'subscriber.initUnsubscribe';

	/**
	 * Init unsubscribe email
	 *
	 * @param Context\InitUnsubscribeEmail $context Init unsubscribe context.
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
				$this->escapeHtml('Try to init unsubscribe email %s'),
				$context->getEmail()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_init_unsubscribe_email/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id' => $api_context->getListId(),
				'email'   => $context->getEmail(),
				'ip'      => $context->getIp(),
				'options' => empty($context->getOptions()) ? '' : $context->getOptions(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Init unsubscribed successful for email %s'),
				$context->getEmail()
			)
		);

		return $result;
	}
}
