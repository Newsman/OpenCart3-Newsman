<?php

namespace Newsman\Action\Anonymise;

/**
 * Anonymise an email in Newsman, on every Newsman list of the stores where the extension is enabled.
 *
 * Used when a customer is deleted or a personal data removal request is processed. Customers are
 * shared by all stores, so the email may be on the list of any store. Newsman is called once per
 * distinct list (user ID + list ID) and email in a request. An email that is not on a list is not an error.
 * Errors are logged and never stop the store action.
 *
 * When a customer is deleted, the setting "Anonymise in Newsman when customers are deleted" of the stores
 * decides: a list used by a store with the setting off gets an unsubscribe instead of the anonymise.
 *
 * @class \Newsman\Action\Anonymise\Email
 */
class Email extends \Newsman\Nzmbase {
	/**
	 * Emails already sent in this request, keyed by email|user ID|list ID
	 *
	 * @var array
	 */
	protected static $sent = array();

	/**
	 * Anonymise the email on every distinct Newsman list, whatever the stores settings
	 * (personal data removal requests)
	 *
	 * @param string $email Email address.
	 *
	 * @return void
	 */
	public function execute($email) {
		$email = trim((string)$email);
		if ($email === '') {
			return;
		}

		foreach ($this->getStoreIdsPerList() as $list_key => $store_id) {
			if (!$this->markSent($email, $list_key)) {
				continue;
			}

			$this->anonymise($email, $store_id);
		}
	}

	/**
	 * A customer is deleted: on every distinct Newsman list, anonymise the email when all the stores using
	 * the list have "Anonymise in Newsman when customers are deleted" on, otherwise unsubscribe it
	 * (the less destructive action wins on a list shared by stores with different settings)
	 *
	 * @param string $email Email address.
	 *
	 * @return void
	 */
	public function executeCustomerDelete($email) {
		$email = trim((string)$email);
		if ($email === '') {
			return;
		}

		foreach ($this->getAllStoreIdsPerList() as $list_key => $store_ids) {
			if (!$this->markSent($email, $list_key)) {
				continue;
			}

			if ($this->isAnonymiseOnCustomerDelete($store_ids)) {
				$this->anonymise($email, $store_ids[0]);
			} else {
				$this->unsubscribe($email, $store_ids[0]);
			}
		}
	}

	/**
	 * One store ID for each distinct Newsman list of the stores where the extension is enabled
	 *
	 * @return array User ID|list ID => store ID.
	 */
	public function getStoreIdsPerList() {
		$lists = array();
		foreach ($this->getAllStoreIdsPerList() as $list_key => $store_ids) {
			$lists[$list_key] = $store_ids[0];
		}

		return $lists;
	}

	/**
	 * The IDs of the stores where the extension is enabled, grouped by distinct Newsman list
	 *
	 * @return array User ID|list ID => store IDs.
	 */
	public function getAllStoreIdsPerList() {
		$lists = array();
		foreach ($this->config->getAllStoreIds() as $store_id) {
			if (!$this->config->isEnabledWithApi($store_id)) {
				continue;
			}

			$list_key = $this->config->getUserId($store_id) . '|' . $this->config->getListId($store_id);
			$lists[$list_key][] = (int)$store_id;
		}

		return $lists;
	}

	/**
	 * Do all the stores using a list anonymise when a customer is deleted?
	 *
	 * @param array $store_ids Store IDs of the list.
	 *
	 * @return bool
	 */
	protected function isAnonymiseOnCustomerDelete($store_ids) {
		foreach ($store_ids as $store_id) {
			if (!$this->config->isAnonymiseOnCustomerDelete($store_id)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Mark the email as sent to a list in this request
	 *
	 * @param string $email    Email address.
	 * @param string $list_key User ID|list ID.
	 *
	 * @return bool False when it was already sent.
	 */
	protected function markSent($email, $list_key) {
		$sent_key = strtolower($email) . '|' . $list_key;
		if (isset(self::$sent[$sent_key])) {
			return false;
		}
		self::$sent[$sent_key] = true;

		return true;
	}

	/**
	 * Anonymise the email on the list of a store
	 *
	 * @param string $email    Email address.
	 * @param int    $store_id Store ID.
	 *
	 * @return void
	 */
	protected function anonymise($email, $store_id) {
		$context = new \Newsman\Service\Context\AnonymiseEmail();
		$context->setListId($this->config->getListId($store_id))
			->setStoreId($store_id)
			->setEmail($email);

		try {
			$anonymise = new \Newsman\Service\AnonymiseEmail($this->registry);
			$anonymise->execute($context);
		} catch (\Exception $e) {
			$this->logError($e, $email, $context->getListId(), 'anonymise');
		} catch (\Throwable $e) {
			$this->logError($e, $email, $context->getListId(), 'anonymise');
		}
	}

	/**
	 * Unsubscribe the email on the list of a store (no confirmation email)
	 *
	 * @param string $email    Email address.
	 * @param int    $store_id Store ID.
	 *
	 * @return void
	 */
	protected function unsubscribe($email, $store_id) {
		$list_id = $this->config->getListId($store_id);

		$this->logger->info(
			sprintf(
				'Unsubscribe instead of anonymise email %s in list %s: anonymise on customer delete is off',
				$email,
				$list_id
			)
		);

		try {
			$user_ip = new \Newsman\User\IpAddress($this->registry);

			$context = new \Newsman\Service\Context\UnsubscribeEmail();
			$context->setListId($list_id)
				->setStoreId($store_id)
				->setEmail($email)
				->setIp($user_ip->getIp());

			$unsubscribe = new \Newsman\Service\UnsubscribeEmail($this->registry);
			$unsubscribe->setExpectedErrorCodes(array(\Newsman\Service\UnsubscribeEmail::ERROR_NO_SUCH_SUBSCRIBER));
			$unsubscribe->execute($context);
		} catch (\Exception $e) {
			$this->logError($e, $email, $list_id, 'unsubscribe');
		} catch (\Throwable $e) {
			$this->logError($e, $email, $list_id, 'unsubscribe');
		}
	}

	/**
	 * Log an anonymise or unsubscribe error; an email that is not on the list is only logged as info
	 *
	 * @param \Exception|\Throwable $e       Error.
	 * @param string                $email   Email address.
	 * @param string                $list_id List ID.
	 * @param string                $action  anonymise or unsubscribe.
	 *
	 * @return void
	 */
	protected function logError($e, $email, $list_id, $action) {
		if (\Newsman\Service\AnonymiseEmail::ERROR_NO_SUCH_SUBSCRIBER === (int)$e->getCode()) {
			$this->logger->info(
				sprintf(
					'Email %s is not on list %s, nothing to %s',
					$email,
					$list_id,
					$action
				)
			);

			return;
		}

		$this->logger->logException($e);
	}
}
