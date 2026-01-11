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
	 * @return bool
	 */
	public function isAllow() {
		return $this->config->isEnabledWithApi($this->config->getCurrentStoreId());
	}

	/**
	 * Execute subscribe email to list
	 *
	 * @param string $email      Email address.
	 * @param string $firstname  First name.
	 * @param string $lastname   Last name.
	 * @param array  $properties Properties array.
	 * @param array  $options    Options array, additional fields.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function execute($email, $firstname, $lastname, $properties = array(), $options = array()) {
		$this->subscribe($email, $firstname, $lastname, $properties, $options);
	}

	/**
	 * Subscribe email to the list
	 *
	 * @param string $email      Email address.
	 * @param string $firstname  First name.
	 * @param string $lastname   Last name.
	 * @param array  $properties Properties array.
	 * @param array  $options    Options array, additional fields.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function subscribe($email, $firstname, $lastname, $properties = array(), $options = array()) {
		if (empty($email) || !$this->isAllow()) {
			return;
		}

		if ($this->config->isNewsletterDoubleOptin($this->config->getCurrentStoreId())) {
			$this->subscribeDoubleOptin($email, $firstname, $lastname, $properties, $options);
		} else {
			$this->subscribeSingleOptin($email, $firstname, $lastname, $properties, $options);
		}
	}

	/**
	 * Subscribe double optin email to list
	 *
	 * @param string $email      Email address.
	 * @param string $firstname  First name.
	 * @param string $lastname   Last name.
	 * @param array  $properties Properties array.
	 * @param array  $options    Options array, additional fields.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function subscribeDoubleOptin($email, $firstname, $lastname, $properties = array(), $options = array()) {
		$context = new \Newsman\Service\Context\InitSubscribeEmail();
		$context->setListId($this->config->getListId($this->config->getCurrentStoreId()))
			->setStoreId($this->config->getCurrentStoreId())
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
	 * @param string $email      Email address.
	 * @param string $firstname  First name.
	 * @param string $lastname   Last name.
	 * @param array  $properties Properties array.
	 * @param array  $options    Options array, additional fields.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function subscribeSingleOptin($email, $firstname, $lastname, $properties = array(), $options = array()) {
		$context = new \Newsman\Service\Context\SubscribeEmail();
		$context->setListId($this->config->getListId($this->config->getCurrentStoreId()))
			->setStoreId($this->config->getCurrentStoreId())
			->setEmail($email)
			->setFirstname($firstname)
			->setLastname($lastname)
			->setIp($this->user_ip->getIp())
			->setProperties($properties);

		try {
			$subscribe     = new \Newsman\Service\SubscribeEmail($this->registry);
			$subscriber_id = $subscribe->execute($context);

			if (!empty($this->config->getSegmentId($this->config->getCurrentStoreId()))) {
				$context = new \Newsman\Service\Context\Segment\AddSubscriber();
				$context->setListId($this->config->getListId($this->config->getCurrentStoreId()))
					->setStoreId($this->config->getCurrentStoreId())
					->setSegmentId($this->config->getSegmentId($this->config->getCurrentStoreId()))
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
	 * @param string $email Email address.
	 *
	 * @return void
	 * @throws \Exception Error exceptions or other.
	 */
	public function unsubscribe($email) {
		if (empty($email) || !$this->isAllow()) {
			return;
		}

		$context = new \Newsman\Service\Context\UnsubscribeEmail();
		$context->setListId($this->config->getListId($this->config->getCurrentStoreId()))
			->setStoreId($this->config->getCurrentStoreId())
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
