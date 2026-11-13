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
	 * Anonymise the email on every distinct Newsman list
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
			$sent_key = strtolower($email) . '|' . $list_key;
			if (isset(self::$sent[$sent_key])) {
				continue;
			}
			self::$sent[$sent_key] = true;

			$this->anonymise($email, $store_id);
		}
	}

	/**
	 * One store ID for each distinct Newsman list of the stores where the extension is enabled
	 *
	 * @return array User ID|list ID => store ID.
	 */
	public function getStoreIdsPerList() {
		$lists = array();
		foreach ($this->config->getAllStoreIds() as $store_id) {
			if (!$this->config->isEnabledWithApi($store_id)) {
				continue;
			}

			$list_key = $this->config->getUserId($store_id) . '|' . $this->config->getListId($store_id);
			if (!isset($lists[$list_key])) {
				$lists[$list_key] = (int)$store_id;
			}
		}

		return $lists;
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
			$this->logError($e, $email, $context->getListId());
		} catch (\Throwable $e) {
			$this->logError($e, $email, $context->getListId());
		}
	}

	/**
	 * Log an anonymise error; an email that is not on the list is only logged as info
	 *
	 * @param \Exception|\Throwable $e       Error.
	 * @param string                $email   Email address.
	 * @param string                $list_id List ID.
	 *
	 * @return void
	 */
	protected function logError($e, $email, $list_id) {
		if (\Newsman\Service\AnonymiseEmail::ERROR_NO_SUCH_SUBSCRIBER === (int)$e->getCode()) {
			$this->logger->info(
				sprintf(
					'Email %s is not on list %s, nothing to anonymise',
					$email,
					$list_id
				)
			);

			return;
		}

		$this->logger->logException($e);
	}
}
