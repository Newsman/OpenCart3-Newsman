<?php

namespace Newsman\Validator;

/**
 * Email address validator
 *
 * @class \Newsman\Validator\Email
 */
class Email {
	/**
	 * Validate an E-mail address
	 *
	 * @param string $email Email address.
	 *
	 * @return false
	 */
	function isValid($email) {
		$email = (string)$email;
		if ($email === '' || strpos($email, '@') === false) {
			return false;
		}

		[$local, $domain] = explode('@', $email, 2);

		if (function_exists('idn_to_ascii')) {
			$ascii_domain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
			if ($ascii_domain === false) {
				return false;
			}
			$email = $local . '@' . $ascii_domain;
		}

		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}
}
