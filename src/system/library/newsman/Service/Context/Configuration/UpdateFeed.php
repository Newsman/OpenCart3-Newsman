<?php

namespace Newsman\Service\Context\Configuration;

/**
 * Class Service Context Configuration UpdateFeed
 *
 * @class \Newsman\Service\Context\Configuration\UpdateFeed
 */
class UpdateFeed extends EmailList {
	/**
	 * Feed ID
	 *
	 * @var string|int
	 */
	protected $feed_id;

	/**
	 * Properties
	 *
	 * @var array
	 */
	protected $properties = array();


	/**
	 * Set feed ID
	 *
	 * @param string $feed_id Feed ID.
	 *
	 * @return $this
	 */
	public function setFeedId($feed_id) {
		$this->feed_id = $feed_id;

		return $this;
	}

	/**
	 * Get feed ID
	 *
	 * @return string
	 */
	public function getFeedId() {
		return $this->feed_id;
	}

	/**
	 * Set properties
	 *
	 * @param array $properties Properties.
	 *
	 * @return $this
	 */
	public function setProperties($properties) {
		$this->properties = $properties;

		return $this;
	}

	/**
	 * Get properties
	 *
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}
}
