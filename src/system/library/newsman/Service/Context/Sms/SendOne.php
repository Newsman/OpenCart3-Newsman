<?php

namespace Newsman\Service\Context\Sms;

use Newsman\Service\Context\Store;

/**
 * Class Service Context SMS Send One
 *
 * @class \Newsman\Service\Context\Sms\SendOne
 */
class SendOne extends Store {
	/**
	 * Phone number
	 *
	 * @var string
	 */
	protected $to;

	/**
	 * Text in SMS
	 *
	 * @var string
	 */
	protected $text;

	/**
	 * Set phone number
	 *
	 * @param string $to Phone number.
	 *
	 * @return $this
	 */
	public function setTo($to) {
		$this->to = $to;

		return $this;
	}

	/**
	 * Get phone number
	 *
	 * @return string
	 */
	public function getTo() {
		return $this->to;
	}

	/**
	 * Set SMS text
	 *
	 * @param string $text SMS text.
	 *
	 * @return $this
	 */
	public function setText($text) {
		$this->text = $text;

		return $this;
	}

	/**
	 * Get SMS text
	 *
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}
}
