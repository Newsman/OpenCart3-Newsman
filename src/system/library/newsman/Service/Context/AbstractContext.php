<?php

namespace Newsman\Service\Context;

/**
 * Class Service Context Abstract Context
 *
 * @class \Newsman\Service\Context\AbstractContext
 */
class AbstractContext {
	/**
	 * Null value sent as request parameter to Newsman API
	 */
	public const NULL_VALUE = 'null';

	/**
	 * Get API request parameter value NULL
	 *
	 * @return string
	 */
	public function getNullValue() {
		return self::NULL_VALUE;
	}
}
