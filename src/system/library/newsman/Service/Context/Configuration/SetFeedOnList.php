<?php

namespace Newsman\Service\Context\Configuration;

/**
 * Class Service Context Configuration SetFeedOnList
 *
 * @class \Newsman\Service\Context\Configuration\SetFeedOnList
 */
class SetFeedOnList extends EmailList {
	/**
	 * The URL of the feed. For the type "fixed", send the name of the feed
	 *
	 * @var string|int
	 */
	protected $url;

	/**
	 * The website for which the feed is being set
	 *
	 * @var string
	 */
	protected $website;

	/**
	 * Type
	 *
	 * @var string
	 */
	protected $type = 'fixed';

	/**
	 * (Optional) If is true an Array containing the key feed_id (the id of the feed) will be returned
	 *
	 * @var int|bool|string
	 */
	protected $return_id = false;

	/**
	 * Set the URL of the feed
	 *
	 * @param string|int $url Feed URL.
	 *
	 * @return $this
	 */
	public function setUrl($url) {
		$this->url = $url;

		return $this;
	}

	/**
	 * Get URL of the feed
	 *
	 * @return int|string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Set website for which the feed is being set
	 *
	 * @param string $website Website / Store URL.
	 *
	 * @return $this
	 */
	public function setWebsite(string $website) {
		$this->website = $website;

		return $this;
	}

	/**
	 * Get a website for which the feed is being set
	 *
	 * @return string
	 */
	public function getWebsite() {
		return $this->website;
	}

	/**
	 * Set the type of feed
	 *
	 * @param string $type Feed type.
	 *
	 * @return $this
	 */
	public function setType(string $type) {
		$this->type = $type;

		return $this;
	}

	/**
	 * Get type of the feed
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Set return ID of the feed
	 *
	 * @param string $return_id Is return ID of the feed.
	 *
	 * @return $this
	 */
	public function setReturnId(string $return_id) {
		$this->return_id = $return_id;

		return $this;
	}

	/**
	 * Get return ID of the feed
	 *
	 * @return string
	 */
	public function getReturnId() {
		return $this->return_id;
	}
}
