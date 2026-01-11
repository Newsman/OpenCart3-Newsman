<?php

namespace Newsman\Service\Context;

/**
 * Class Service Context Init Subscribe Email
 *
 * @class \Newsman\Service\Context\InitSubscribeEmail
 */
class InitSubscribeEmail extends SubscribeEmail {
	/**
	 * Options request parameter API
	 *
	 * @var array|null
	 */
	protected $options;

	/**
	 * Set options
	 *
	 * @param array|string $options Options.
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
