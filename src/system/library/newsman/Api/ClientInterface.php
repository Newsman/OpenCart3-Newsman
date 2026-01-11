<?php

namespace Newsman\Api;

/**
 * Class API Client interface
 *
 * @class \Newsman\Api\ClientInterface
 */
interface ClientInterface {
	/**
	 * Make API GET request
	 *
	 * @param ContextInterface $context API request context.
	 * @param array            $params GET parameters.
	 *
	 * @return array Response from API
	 */
	public function get($context, $params = array());

	/**
	 * Make API POST request
	 *
	 * @param ContextInterface $context API request context.
	 * @param array            $get_params GET parameters.
	 * @param array            $post_params POST parameters.
	 *
	 * @return array Response from API
	 */
	public function post($context, $get_params = array(), $post_params = array());

	/**
	 * Make API request
	 *
	 * @param ContextInterface $context API request context.
	 * @param string           $method GET or POST request type.
	 * @param array            $get_params GET parameters.
	 * @param array            $post_params POST parameters.
	 *
	 * @return array
	 */
	public function request($context, $method, $get_params = array(), $post_params = array());

	/**
	 * Get HTTP response status code
	 *
	 * @return int|string
	 */
	public function getStatus();

	/**
	 * Get error code from API, HTTP Error Code or JSON error == 1
	 *
	 * @return int
	 */
	public function getErrorCode();

	/**
	 * Get error message from API, HTTP error body message or JSON parse error
	 *
	 * @return string
	 */
	public function getErrorMessage();

	/**
	 * API error check
	 *
	 * @return bool
	 */
	public function hasError();
}
