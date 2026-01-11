<?php

namespace Newsman\Service\Context\Segment;

use Newsman\Service\Context\Store;

/**
 * Class Service Context Segment Add Subscriber
 *
 * @class \Newsman\Service\Context\Segment\AddSubscriber
 */
class AddSubscriber extends Store {
	/**
	 * Segment ID
	 *
	 * @var string|int
	 */
	protected $segment_id;

	/**
	 * Subscriber ID
	 *
	 * @var string
	 */
	protected $subscriber_id;

	/**
	 * Set segment ID
	 *
	 * @param string $segment_id Segment ID.
	 *
	 * @return $this
	 */
	public function setSegmentId($segment_id) {
		$this->segment_id = $segment_id;

		return $this;
	}

	/**
	 * Get segment ID
	 *
	 * @return string
	 */
	public function getSegmentId() {
		return $this->segment_id;
	}

	/**
	 * Set subscriber ID
	 *
	 * @param string $subscriber_id Newsman subscriber ID.
	 *
	 * @return $this
	 */
	public function setSubscriberId(string $subscriber_id) {
		$this->subscriber_id = $subscriber_id;

		return $this;
	}

	/**
	 * Get Newsman subscriber ID
	 *
	 * @return string
	 */
	public function getSubscriberId() {
		return $this->subscriber_id;
	}
}
