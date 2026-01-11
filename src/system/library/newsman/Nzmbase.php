<?php

namespace Newsman;

/**
 * Base class for Newsman plugin classes
 */
class Nzmbase {
	/**
	 * @var \Registry
	 */
	protected $registry;

	/**
	 * @var \Newsman\Nzmconfig
	 */
	protected $config;

	/**
	 * @var \Newsman\Nzmlogger
	 */
	protected $logger;

	/**
	 * @var \Event
	 */
	protected $event;

	/**
	 * Constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		$this->registry = $registry;
		$this->registry->load->library('newsman/nzmconfig');
		$this->config = $this->registry->get('nzmconfig');
		$this->registry->load->library('newsman/nzmlogger');
		$this->logger = $this->registry->get('nzmlogger');
		$this->event = $this->registry->get('event');
	}

	/**
	 * @return \Newsman\Nzmconfig
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @param \Newsman\Nzmconfig $config
	 *
	 * @return $this
	 */
	public function setConfig($config) {
		$this->config = $config;

		return $this;
	}

	/**
	 * @return \Event
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * @param \Event $event
	 *
	 * @return $this
	 */
	public function setEvent($event) {
		$this->event = $event;

		return $this;
	}

	/**
	 * @return \Newsman\Nzmlogger
	 */
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * @param \Newsman\Nzmlogger $logger
	 *
	 * @return $this
	 */
	public function setLogger($logger) {
		$this->logger = $logger;

		return $this;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function escapeHtml($string) {
		return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', false);
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public function escapeJs($string) {
		if (!is_string($string)) {
			$string = (string)$string;
		}

		if ($string === '' || ctype_digit($string)) {
			return $string;
		}

		return preg_replace_callback(
			'/[^a-z0-9,\._]/iSu',
			function ($matches) {
				$chr = $matches[0];
				if (strlen($chr) != 1) {
					$chr = mb_convert_encoding($chr, 'UTF-16BE', 'UTF-8');
					$chr = ($chr === false) ? '' : $chr;
				}

				return sprintf('\\u%04s', strtoupper(bin2hex($chr)));
			},
			$string
		);
	}
}
