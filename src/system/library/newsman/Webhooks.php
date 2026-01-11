<?php

namespace Newsman;

/**
 * Class Webhooks
 *
 * @class \Newsman\Webhooks
 */
class Webhooks extends \Newsman\Nzmbase {
	/**
	 * Execute webhooks
	 *
	 * @param array $events
	 *
	 * @return void
	 */
	public function execute($events) {
		try {
			if (is_string($events)) {
				$events = json_decode(html_entity_decode($events), true);
			}

			if (!is_array($events)) {
				$this->logger->error('Invalid events format');
				$renderer = new \Newsman\Export\Renderer($this->registry);
				$renderer->displayJson(array('error' => 'Invalid events format'));
			}

			$this->logger->info('Processing newsman webhooks');

			$result = array();

			foreach ($events as $event) {
				if (!isset($event['type'])) {
					continue;
				}

				$this->logger->info(sprintf('Processing webhook event type: %s', $event['type']));

				switch ($event['type']) {
					case 'unsub':
						$result[] = $this->unsubscribe($event);
						break;
					case 'subscribe':
					case 'subscribe_confirm':
						$result[] = $this->subscribe($event);
						break;
					case 'import':
						$result[] = array();
						break;
				}
			}

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		} catch (\Exception $e) {
			$this->logger->logException($e);

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson(array('error' => $e->getMessage()));
		}
	}

	/**
	 * Unsubscribe event
	 *
	 * @param array $event
	 *
	 * @return array
	 */
	protected function unsubscribe($event) {
		if (!isset($event['data']['email'])) {
			return array('error' => 'Email not found');
		}

		$email = $event['data']['email'];
		$this->logger->debug(sprintf('Unsubscribe email: %s', $email));

		$this->registry->db->query("UPDATE " . DB_PREFIX . "customer SET newsletter = '0' WHERE email = '" . $this->registry->db->escape($email) . "' AND store_id = '" . (int)$this->config->getCurrentStoreId() . "'");

		return array('success' => true, 'email' => $email);
	}

	/**
	 * Subscribe event
	 *
	 * @param array $event
	 *
	 * @return array
	 */
	protected function subscribe($event) {
		if (!isset($event['data']['email'])) {
			return array('error' => 'Email not found');
		}

		$email = $event['data']['email'];
		$this->logger->debug(sprintf('Subscribe email: %s', $email));

		$this->registry->db->query("UPDATE " . DB_PREFIX . "customer SET newsletter = '1' WHERE email = '" . $this->registry->db->escape($email) . "' AND store_id = '" . (int)$this->config->getCurrentStoreId() . "'");

		return array('success' => true, 'email' => $email);
	}
}
