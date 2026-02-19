<?php

namespace Newsman\Export\Retriever;

use Newsman\Export\V1\ApiV1Exception;

/**
 * Subscribe an email address to the store's newsletter (API v1: subscriber.subscribe).
 *
 * Updates the customer newsletter flag directly in the database, mirroring
 * the logic in Webhooks::subscribe().
 *
 * @class \Newsman\Export\Retriever\SubscriberSubscribe
 */
class SubscriberSubscribe extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process subscriber subscribe.
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
			throw new ApiV1Exception(3100, 'Missing "email" parameter', 400);
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new ApiV1Exception(3101, 'Invalid email address: ' . $email, 400);
		}

		$this->logger->info(
			sprintf('subscriber.subscribe: %s, store %d', $email, $store_id)
		);

		$this->registry->db->query(
			"UPDATE " . DB_PREFIX . "customer SET newsletter = '1' WHERE email = '" .
			$this->registry->db->escape($email) . "'"
		);

		return array('success' => true, 'email' => $email);
	}
}
