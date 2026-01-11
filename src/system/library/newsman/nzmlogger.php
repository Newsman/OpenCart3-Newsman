<?php

namespace Newsman;

/**
 * Logger Newsman classes
 */
class Nzmlogger extends \Newsman\Library {
	/**
	 * Log file name
	 */
	const FILENAME = 'newsman_{date}.log';

	/**#@+
	 * Logging level types
	 */
	const TYPE_EMERGENCY = 'EMERGENCY';

	const TYPE_ALERT = 'ALERT';

	const TYPE_CRITICAL = 'CRITICAL';

	const TYPE_ERROR = 'ERROR';

	const TYPE_WARNING = 'WARNING';

	const TYPE_NOTICE = 'NOTICE';

	const TYPE_INFO = 'INFO';

	const TYPE_DEBUG = 'DEBUG';

	const TYPE_NONE = 'NONE';
	/**#@-*/

	/**#@+
	 * Logging level codes
	 */
	const EMERGENCY = 600;

	const ALERT = 550;

	const CRITICAL = 500;

	const ERROR = 400;

	const WARNING = 300;

	const NOTICE = 250;

	const INFO = 200;

	const DEBUG = 100;

	const NONE = 1;
	/**#@-*/

	/**
	 * Newsman Config
	 *
	 * @var \Newsman\Nzmconfig
	 */
	protected $config;

	/**
	 * @var \Log
	 */
	protected $logger;

	/**
	 * Constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->registry->load->library('newsman/nzmconfig');
		$this->config = $this->registry->get('nzmconfig');

		$this->logger = new \Log(str_replace('{date}', date('Y-m-d'), self::FILENAME));
	}

	/**
	 * Log message
	 *
	 * @param string $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return void
	 */
	public function log($level, $message, array $context = []) {
		if ($this->config->getLogSeverity() <= self::NONE) {
			return;
		}
		$line = sprintf('[%s] %s %s', strtoupper($level), (string)$message, ($context) ? json_encode($context) : '');
		$this->logger->write($line);
	}

	/**
	 * Emergency level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function emergency($message, array $context = []) {
		if ($this->config->getLogSeverity() <= self::NONE) {
			return;
		}
		$this->log(self::EMERGENCY, $message, $context);
	}

	/**
	 * Alert level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function alert($message, array $context = []) {
		if ($this->config->getLogSeverity() <= self::NONE) {
			return;
		}
		$this->log(self::ALERT, $message, $context);
	}

	/**
	 * Critical level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function critical($message, array $context = []) {
		if ($this->config->getLogSeverity() <= self::NONE) {
			return;
		}
		$this->log(self::CRITICAL, $message, $context);
	}

	/**
	 * Error level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function error($message, array $context = []) {
		if ($this->config->getLogSeverity() <= self::NONE) {
			return;
		}
		$this->log(self::ERROR, $message, $context);
	}

	/**
	 * Warning level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function warning($message, array $context = []) {
		if ($this->config->getLogSeverity() > self::WARNING) {
			return;
		}
		$this->log(self::WARNING, $message, $context);
	}

	/**
	 * Notice level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function notice($message, array $context = []) {
		if ($this->config->getLogSeverity() > self::NOTICE) {
			return;
		}
		$this->log(self::NOTICE, $message, $context);
	}

	/**
	 * Info level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function info($message, array $context = []) {
		if ($this->config->getLogSeverity() > self::INFO) {
			return;
		}
		$this->log(self::INFO, $message, $context);
	}

	/**
	 * Debug level log
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function debug($message, array $context = []) {
		if ($this->config->getLogSeverity() > self::DEBUG) {
			return;
		}
		$this->log(self::DEBUG, $message, $context);
	}

	/**
	 * Log exception
	 *
	 * @param \Exception $e Exception to log.
	 * @param array      $context
	 *
	 * @return void
	 */
	public function logException($e, array $context = []) {
		$this->error(
			'[' . $e->getCode() . '] ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in ' . $e->getFile() .
			"\n" . $e->getTraceAsString(),
			$context
		);
	}

	/**
	 * Get logging level codes
	 *
	 * @return array
	 */
	public function getCodes() {
		return array(
			self::EMERGENCY => self::TYPE_EMERGENCY,
			self::ALERT     => self::TYPE_ALERT,
			self::CRITICAL  => self::TYPE_CRITICAL,
			self::ERROR     => self::TYPE_ERROR,
			self::WARNING   => self::TYPE_WARNING,
			self::NOTICE    => self::TYPE_NOTICE,
			self::INFO      => self::TYPE_INFO,
			self::DEBUG     => self::TYPE_DEBUG,
			self::NONE      => self::TYPE_NONE
		);
	}

	/**
	 * Get logging level code by type
	 *
	 * @param string $type
	 *
	 * @return int|false
	 */
	public function getCodeByType($type) {
		return array_search($type, $this->getCodes(), true);
	}
}
