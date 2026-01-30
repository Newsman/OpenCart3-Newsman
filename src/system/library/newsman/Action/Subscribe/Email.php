<?php

namespace Newsman\Action\Subscribe;

/**
 * Subscribe or unsubscribe email action
 *
 * @class \Newsman\Action\Subscribe\Email
 */
class Email extends \Newsman\Nzmbase {
	/**
	 * @var \Newsman\User\IpAddress
	 */
	protected $user_ip;

	/**
	 * Class constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->user_ip = new \Newsman\User\IpAddress($registry);
	}

	/**
	 * Is allow action to run
	 *
	 * @param int|null $store_id
	 *
	 * @return bool
	 */
	public function isAllow($store_id = null) {
		if ($store_id === null) {
			$store_id = $this->config->getCurrentStoreId();
		}

		return $this->config->isEnabledWithApi($store_id);
	}

	/**
	 * Execute subscribe email to list
	 *
	 * @param string   $email      Email address.
	 * @param string   $firstname  First name.
	 * @param string   $lastname   Last name.
	 * @param array    $properties Properties array.
	 * @param array    $options    Options array, additional fields.
	 * @param int|null $store_id   Store ID.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function execute($email, $firstname, $lastname, $properties = array(), $options = array(), $store_id = null) {
		$this->subscribe($email, $firstname, $lastname, $properties, $options, $store_id);
	}

	/**
	 * Subscribe email to the list
	 *
	 * @param string   $email      Email address.
	 * @param string   $firstname  First name.
	 * @param string   $lastname   Last name.
	 * @param array    $properties Properties array.
	 * @param array    $options    Options array, additional fields.
	 * @param int|null $store_id   Store ID.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function subscribe($email, $firstname, $lastname, $properties = array(), $options = array(), $store_id = null) {
		if ($store_id === null) {
			$store_id = $this->config->getCurrentStoreId();
		}

		if (empty($email) || !$this->isAllow($store_id)) {
			return;
		}

		if ($this->config->isNewsletterDoubleOptin($store_id)) {
			$this->subscribeDoubleOptin($email, $firstname, $lastname, $properties, $options, $store_id);
		} else {
			$this->subscribeSingleOptin($email, $firstname, $lastname, $properties, $options, $store_id);
		}
	}

	/**
	 * Subscribe double optin email to list
	 *
	 * @param string   $email      Email address.
	 * @param string   $firstname  First name.
	 * @param string   $lastname   Last name.
	 * @param array    $properties Properties array.
	 * @param array    $options    Options array, additional fields.
	 * @param int|null $store_id   Store ID.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function subscribeDoubleOptin($email, $firstname, $lastname, $properties = array(), $options = array(), $store_id = null) {
		if ($store_id === null) {
			$store_id = $this->config->getCurrentStoreId();
		}

		$context = new \Newsman\Service\Context\InitSubscribeEmail();
		$context->setListId($this->config->getListId($store_id))
			->setStoreId($store_id)
			->setEmail($email)
			->setFirstname($firstname)
			->setLastname($lastname)
			->setIp($this->user_ip->getIp())
			->setProperties($properties)
			->setOptions($options);

		try {
			$init_subscribe = new \Newsman\Service\InitSubscribeEmail($this->registry);
			$init_subscribe->execute($context);
		} catch (\Exception $e) {
			$this->logger->logException($e);
		}
	}

	/**
	 * Subscribe single optin email to list
	 *
	 * @param string   $email      Email address.
	 * @param string   $firstname  First name.
	 * @param string   $lastname   Last name.
	 * @param array    $properties Properties array.
	 * @param array    $options    Options array, additional fields.
	 * @param int|null $store_id   Store ID.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function subscribeSingleOptin($email, $firstname, $lastname, $properties = array(), $options = array(), $store_id = null) {
		if ($store_id === null) {
			$store_id = $this->config->getCurrentStoreId();
		}

		$context = new \Newsman\Service\Context\SubscribeEmail();
		$context->setListId($this->config->getListId($store_id))
			->setStoreId($store_id)
			->setEmail($email)
			->setFirstname($firstname)
			->setLastname($lastname)
			->setIp($this->user_ip->getIp())
			->setProperties($properties);

		try {
			$subscribe     = new \Newsman\Service\SubscribeEmail($this->registry);
			$subscriber_id = $subscribe->execute($context);

			if (!empty($this->config->getSegmentId($store_id))) {
				$context = new \Newsman\Service\Context\Segment\AddSubscriber();
				$context->setListId($this->config->getListId($store_id))
					->setStoreId($store_id)
					->setSegmentId($this->config->getSegmentId($store_id))
					->setSubscriberId($subscriber_id);

				try {
					$add_subscriber = new \Newsman\Service\Segment\AddSubscriber($this->registry);
					$add_subscriber->execute($context);
				} catch (\Exception $e) {
					$this->logger->logException($e);
				}
			}
		} catch (\Exception $e) {
			$this->logger->logException($e);
		}
	}

	/**
	 * Unsubscribe email from a list
	 *
	 * @param string   $email    Email address.
	 * @param int|null $store_id Store ID.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function unsubscribe($email, $store_id = null) {
		if ($store_id === null) {
			$store_id = $this->config->getCurrentStoreId();
		}

		if (empty($email) || !$this->isAllow($store_id)) {
			return;
		}

		$context = new \Newsman\Service\Context\UnsubscribeEmail();
		$context->setListId($this->config->getListId($store_id))
			->setStoreId($store_id)
			->setEmail($email)
			->setIp($this->user_ip->getIp());

		try {
			$unsubscribe = new \Newsman\Service\UnsubscribeEmail($this->registry);
			$unsubscribe->execute($context);
		} catch (\Exception $e) {
			$this->logger->logException($e);
		}
	}
}
