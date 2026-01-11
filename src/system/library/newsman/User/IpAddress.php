<?php

namespace Newsman\User;

/**
 * Get the user IP address
 *
 * @class \Newsman\User\IpAddress
 */
class IpAddress extends \Newsman\Nzmbase {
	/**
	 * Config
	 *
	 * @var HostIpAddress
	 */
	protected $host_ip_address;

	/**
	 * Config
	 *
	 * @var RemoteIpAddress
	 */
	protected $remote_ip_address;

	/**
	 * Class construct
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->host_ip_address = new HostIpAddress($registry);
		$this->remote_ip_address = new RemoteIpAddress($registry);
	}

	/**
	 * Get the subscriber ip address. (Necessary for Newsman subscription).
	 *
	 * @return string The ip address.
	 */
	public function getIp() {
		if ($this->config->isDeveloperActiveUserIp() && $this->config->getDeveloperUserIp()) {
			return $this->config->getDeveloperUserIp();
		}

		if (!$this->config->isSendUerIp()) {
			return $this->host_ip_address->getIp();
		}

		$ip = $this->remote_ip_address->getIp();

		if ('127.0.0.1' === $ip || empty($ip)) {
			return $this->host_ip_address->getIp();
		}

		return $ip;
	}
}
