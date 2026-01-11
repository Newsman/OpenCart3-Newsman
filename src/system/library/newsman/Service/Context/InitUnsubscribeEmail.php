<?php

namespace Newsman\Service\Context;

/**
 * Class Service Context Init Unsubscribe Email
 *
 * @class \Newsman\Service\Context\InitUnsubscribeEmail
 */
class InitUnsubscribeEmail extends UnsubscribeEmail {
	/**
	 * Options request parameter API
	 *
	 * @var array|null
	 */
	protected $options;

	/**
	 * Set options
	 *
	 * @param array $options Options.
	 *
	 * @return $this
	 */
	public function setOptions($options) {
		$this->options = $options;

		return $this;
	}

	/**
	 * Get options
	 *
	 * @return array|string
	 */
	public function getOptions() {
		return $this->options;
	}
}
