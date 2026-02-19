<?php

namespace Newsman\Export\V1;

/**
 * Exception carrying an API v1 structured error code and HTTP status.
 *
 * @class \Newsman\Export\V1\ApiV1Exception
 */
class ApiV1Exception extends \RuntimeException {
	/**
	 * API v1 numeric error code.
	 *
	 * @var int
	 */
	private $error_code;

	/**
	 * HTTP status code for the response.
	 *
	 * @var int
	 */
	private $http_status;

	/**
	 * @param int    $error_code  API v1 numeric error code.
	 * @param string $message     Human-readable error message.
	 * @param int    $http_status HTTP status code.
	 */
	public function __construct($error_code, $message, $http_status = 500) {
		$this->error_code = (int) $error_code;
		$this->http_status = (int) $http_status;
		parent::__construct($message);
	}

	/**
	 * Get API v1 error code.
	 *
	 * @return int
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * Get HTTP status code for the response.
	 *
	 * @return int
	 */
	public function getHttpStatus() {
		return $this->http_status;
	}
}
