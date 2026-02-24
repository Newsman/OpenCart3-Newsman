<?php

namespace Newsman\Util;

/**
 * Class Server IP Resolver
 *
 * Resolves the server's public IP address by querying well-known free
 * IP-lookup services in random order and falling back to $_SERVER['SERVER_ADDR'].
 *
 * @class \Newsman\Util\ServerIpResolver
 */
class ServerIpResolver {
	/**
	 * Free IP-lookup service URLs.
	 * Each service returns the public IP as plain text.
	 *
	 * @var array
	 */
	protected $services = array(
		'https://api.ipify.org',
		'https://ipinfo.io/ip',
		'https://ifconfig.me/ip',
		'https://icanhazip.com',
	);

	/**
	 * Resolve the server's public IP address.
	 *
	 * Tries the lookup services in random order and returns the first valid
	 * IP address found. Falls back to $_SERVER['SERVER_ADDR'] if all services
	 * are unreachable.
	 *
	 * @return string
	 */
	public function resolve() {
		$services = $this->services;
		shuffle($services);

		foreach ($services as $url) {
			$ip = $this->fetchFromService($url);
			if ($this->isValidIp($ip)) {
				return $ip;
			}
		}

		return isset($_SERVER['SERVER_ADDR']) ? (string)$_SERVER['SERVER_ADDR'] : '';
	}

	/**
	 * Fetch the IP from a single lookup service using native PHP cURL.
	 *
	 * @param string $url Lookup service URL.
	 * @return string Trimmed response body, or empty string on failure.
	 */
	private function fetchFromService($url) {
		if (!function_exists('curl_init')) {
			return '';
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		$result   = curl_exec($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($result === false || $httpCode !== 200) {
			return '';
		}

		return trim((string)$result);
	}

	/**
	 * Check whether a string is a valid IP address.
	 *
	 * @param string $ip Value to check.
	 * @return bool
	 */
	private function isValidIp($ip) {
		return !empty($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false;
	}
}
