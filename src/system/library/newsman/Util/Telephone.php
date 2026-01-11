<?php

namespace Newsman\Util;

/**
 * Util functions for telephone number
 *
 * @class \Newsman\Util\Telephone
 */
class Telephone extends \Newsman\Nzmbase {
	/**
	 * Clean telephone number
	 *
	 * @param string $phone Telephone number
	 *
	 * @return bool
	 */
	public function clean($phone) {
		if (empty($phone)) {
			return '';
		}
		$phone = str_replace('+', '', $phone);
		$phone = preg_replace('/\s\s+/', ' ', $phone);
		$this->event->trigger('newsman/util_telephone_clean/after', array(&$phone));

		return trim($phone);
	}

	/**
	 * Add RO prefix to telephone number
	 *
	 * @param string $phone Telephone number
	 *
	 * @return string
	 */
	public function addRoPrefix($phone) {
		if (empty($phone)) {
			return $phone;
		}
		if (0 === strpos($phone, '40')) {
			return $phone;
		}

		if (0 === strpos($phone, '0')) {
			$phone = '4' . $phone;
		} else {
			$phone = '40' . $phone;
		}
		$this->event->trigger('newsman/util_telephone_add_ro_prefix/after', array(&$phone));

		return $phone;
	}
}
