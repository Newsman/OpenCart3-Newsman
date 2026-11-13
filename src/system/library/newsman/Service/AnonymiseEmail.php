<?php

namespace Newsman\Service;

/**
 * API Class Service Anonymise Email in a List
 *
 * @class \Newsman\Service\AnonymiseEmail
 */
class AnonymiseEmail extends AbstractService {
	/**
	 * Anonymise email in a list Newsman API endpoint
	 *
	 * Newsman accepts the request right away and anonymises the subscriber shortly after:
	 * the email is replaced and the subscriber is unsubscribed.
	 *
	 * @see https://kb.newsman.com/api/1.2/subscriber.anonymiseEmail
	 */
	public const ENDPOINT = 'subscriber.anonymiseEmail';

	/**
	 * Newsman API error code: the email is not on the list (nothing to anonymise)
	 */
	public const ERROR_NO_SUCH_SUBSCRIBER = 121;

	/**
	 * Anonymise email
	 *
	 * @param Context\AnonymiseEmail $context Anonymise email context.
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
				$this->escapeHtml('Try to anonymise email %s in list %s'),
				$context->getEmail(),
				$api_context->getListId()
			)
		);

		$client = $this->createApiClient();
		$client->setExpectedErrorCodes(array(self::ERROR_NO_SUCH_SUBSCRIBER));
		$this->event->trigger('newsman/service_anonymise_email/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id' => $api_context->getListId(),
				'email'   => $context->getEmail(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Anonymise requested for email %s in list %s'),
				$context->getEmail(),
				$api_context->getListId()
			)
		);

		return $result;
	}
}
