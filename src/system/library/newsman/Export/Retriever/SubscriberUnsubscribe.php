<?php

namespace Newsman\Export\Retriever;

use Newsman\Export\V1\ApiV1Exception;

/**
 * Unsubscribe an email address from the store's newsletter (API v1: subscriber.unsubscribe).
 *
 * Updates the customer newsletter flag directly in the database, mirroring
 * the logic in Webhooks::unsubscribe(). The operation is idempotent:
 * if the email is not found, success is returned.
 *
 * @class \Newsman\Export\Retriever\SubscriberUnsubscribe
 */
class SubscriberUnsubscribe extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process subscriber unsubscribe.
	 *
	 * @param array    $data     Request data.
	 * @param null|int $store_id Store ID.
	 *
	 * @return array
	 * @throws ApiV1Exception
	 */
	public function process($data = array(), $store_id = null) {
		$email = isset($data['email']) ? trim((string) $data['email']) : '';
		if (empty($email)) {
			throw new ApiV1Exception(3200, 'Missing "email" parameter', 400);
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new ApiV1Exception(3201, 'Invalid email address: ' . $email, 400);
		}

		$this->logger->info(
			sprintf('subscriber.unsubscribe: %s, store %d', $email, $store_id)
		);

		$this->registry->db->query(
			"UPDATE " . DB_PREFIX . "customer SET newsletter = '0' WHERE email = '" .
			$this->registry->db->escape($email) . "'"
		);

		return array('success' => true, 'email' => $email);
	}
}
