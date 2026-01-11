<?php

namespace Newsman\User;

/**
 * Get user remote IP address
 *
 * @class \Newsman\User\RemoteIpAddress
 */
class RemoteIpAddress extends \Newsman\Nzmbase {
	/**
	 * Get the remote ip address.
	 *
	 * @return string The ip address.
	 */
	public function getIp() {
		$real = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '';
		$cl = isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : '';
		$forward = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
		$forward2 = isset($_SERVER['HTTP_X_FORWARDED']) ? $_SERVER['HTTP_X_FORWARDED'] : '';
		$forward3 = isset($_SERVER['HTTP_FORWARDED_FOR']) ? $_SERVER['HTTP_FORWARDED_FOR'] : '';
		$forward4 = isset($_SERVER['HTTP_FORWARDED']) ? $_SERVER['HTTP_FORWARDED'] : '';
		$remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

		if (filter_var($real, FILTER_VALIDATE_IP)) {
			$ip = $real;
		} elseif (filter_var($cl, FILTER_VALIDATE_IP)) {
			$ip = $cl;
		} elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
			$ip = $forward;
		} elseif (filter_var($forward2, FILTER_VALIDATE_IP)) {
			$ip = $forward2;
		} elseif (filter_var($forward3, FILTER_VALIDATE_IP)) {
			$ip = $forward3;
		} elseif (filter_var($forward4, FILTER_VALIDATE_IP)) {
			$ip = $forward4;
		} else {
			$ip = $remote;
		}

		return $ip;
	}
}
