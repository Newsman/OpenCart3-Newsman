<?php

namespace Newsman\Api;

/**
 * Class API Client
 *
 * @class \Newsman\Api\Client
 */
class Client extends \Newsman\Nzmbase implements ClientInterface {
	/**
	 * HTTP status code
	 *
	 * @var int|string|null
	 */
	protected $status;

	/**
	 * API error code
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * API error message
	 *
	 * @var string
	 */
	protected $error_message;

	/**
	 * Make API GET request
	 *
	 * @param ContextInterface $context API request context.
	 * @param array            $params GET parameters.
	 *
	 * @return array Response from API
	 */
	public function get($context, $params = array()) {
		$this->event->trigger('newsman/api_client_get_params/before', array($context, &$params));

		return $this->request($context, 'GET', $params);
	}

	/**
	 * Make API POST request
	 *
	 * @param ContextInterface $context API request context.
	 * @param array            $get_params GET parameters.
	 * @param array            $post_params POST parameters.
	 *
	 * @return array Response from API
	 */
	public function post($context, $get_params = array(), $post_params = array()) {
		$this->event->trigger('newsman/api_client_post_params/before', array($context, &$get_params, &$post_params));

		return $this->request($context, 'POST', $get_params, $post_params);
	}

	/**
	 * Make API request
	 *
	 * @param ContextInterface $context API request context.
	 * @param string           $method GET or POST request type.
	 * @param array            $get_params GET parameters.
	 * @param array            $post_params POST parameters.
	 *
	 * @return array|string|mixed
	 */
	public function request($context, $method, $get_params = array(), $post_params = array()) {
		$this->event->trigger(
			'newsman/api_client_request_params/before',
			array(
				$context,
				&$method,
				&$get_params,
				&$post_params
			)
		);

		$context = isset($filter['context']) ? $filter['context'] : $context;
		$method = isset($filter['method']) ? $filter['method'] : $method;
		$get_params = isset($filter['get_params']) ? $filter['get_params'] : $get_params;
		$post_params = isset($filter['post_params']) ? $filter['post_params'] : $post_params;

		$this->status = null;
		$this->error_message = null;
		$this->error_code = 0;
		$result = array();

		$url = $this->config->getApiUrl();
		$request_uri = sprintf(
			'%s/rest/%s/%s/%s.json',
			$this->config->getApiVersion(),
			$context->getUserId(),
			$context->getApiKey(),
			$context->getEndpoint()
		);

		$this->event->trigger(
			'newsman/api_client_request_url/after',
			array(
				&$request_uri,
				$this->config->getApiVersion(),
				$context->getUserId(),
				$context->getApiKey(),
				$context->getEndpoint()
			)
		);

		$url .= $request_uri;

		$log_url = $url;
		$log_get_params = $get_params;
		if (is_array($get_params) && !empty($get_params)) {
			$url .= '?' . http_build_query($get_params);
			if (isset($log_get_params['props']) && isset($log_get_params['props']['auth_header_name'])) {
				$log_get_params['props']['auth_header_name'] = '****';
			}
			if (isset($log_get_params['props']) && isset($log_get_params['props']['auth_header_value'])) {
				$log_get_params['props']['auth_header_value'] = '****';
			}
			$log_url .= '?' . http_build_query($log_get_params);
		}
		$log_hash = uniqid();
		$this->logger->debug('[' . $log_hash . '] ' . str_replace($context->getApiKey(), '****', $log_url));

		try {
			$start_time = microtime(true);
			if ('POST' === $method) {
				$this->event->trigger(
					'newsman/api_client_request_post_args/before',
					array(&$url, &$post_params, $context)
				);
				$remote_result = $this->execute('post', $url, $context, $post_params);

				$this->logger->debug(json_encode($post_params));
			} else {
				$this->event->trigger('newsman/api_client_request_get_args/before', array(&$url, $context));
				$remote_result = $this->execute('get', $url, $context);
			}
			$elapsed_ms = round((microtime(true) - $start_time) * 1000);
			$this->logger->debug(
				sprintf(
					'[%s] Requested in %s',
					$log_hash,
					$this->formatTimeDuration($elapsed_ms)
				)
			);

			if (!empty($remote_result['error'])) {
				throw new \Exception($remote_result['error'], (int)$remote_result['status']);
			}

			$this->status = (int)$remote_result['status'];
			if (200 === $this->status) {
				try {
					$result = json_decode($remote_result['body'], true);
					$api_error = $this->parseApiError($result);
					if (false !== $api_error) {
						$this->error_code = (int)$api_error['code'];
						$this->error_message = $api_error['message'];
						$this->logger->warning($this->error_code . ' | ' . $this->error_message);
					} else {
						$this->logger->notice(json_encode($result));
					}
				} catch (\Exception $e) {
					$this->error_code = 1;
					$this->error_message = $e->getMessage();
					$this->logger->logException($e);

					return array();
				}
			} else {
				$this->error_code = (int)$this->status;
				try {
					if (stripos($remote_result['body'], '{') !== false) {
						$body = json_decode($remote_result['body'], true);
						$api_error = $this->parseApiError($body);
						if (false !== $api_error) {
							$this->error_code = (int)$api_error['code'];
							$this->error_message = $api_error['message'];
						} else {
							$this->error_message = 'Error: ' . $this->error_code;
						}
					}
				} catch (\Exception $e) {
					$this->error_message = 'Error: ' . $this->error_code;
				}
				$this->logger->error($this->status . ' | ' . $remote_result['body']);
			}
		} catch (\Exception $e) {
			$this->error_code = (int)$e->getCode();
			$this->error_message = $e->getMessage();
			$this->logger->logException($e);
		}

		return $result;
	}

	/**
	 * Execute CURL request
	 *
	 * @param string           $method
	 * @param string           $url
	 * @param ContextInterface $context
	 * @param array            $post_params
	 *
	 * @return array CURL response.
	 */
	public function execute($method, $url, $context, $post_params = array()) {
		$error = '';

		$method = strtolower(trim($method));
		$curl_options = array(
			CURLOPT_URL            => $url,
			CURLOPT_HEADER         => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => array(),
			CURLOPT_CONNECTTIMEOUT => $this->config->getApiTimeout($context->getStoreId()),
			CURLOPT_TIMEOUT        => $this->config->getApiTimeout($context->getStoreId()),
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0
		);

		$curl_options[CURLOPT_HTTPHEADER][] = 'Accept-Charset: utf-8';
		$curl_options[CURLOPT_HTTPHEADER][] = 'Accept: application/json';
		$curl_options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';

		switch ($method) {
			case 'get':
				$curl_options[CURLOPT_HTTPGET] = true;

				break;
			case 'post':
				$curl_options[CURLOPT_POST] = true;
				$curl_options[CURLOPT_POSTFIELDS] = json_encode($post_params);

				break;
		}

		$ch = curl_init();
		curl_setopt_array($ch, $curl_options);

		$this->event->trigger(
			'newsman/api_client_execute_curl_options/before',
			array(&$curl_options, $method)
		);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			$curl_code = curl_errno($ch);

			$constant = get_defined_constants(true);
			$curl_constant = preg_grep('/^CURLE_/', array_flip($constant['curl']));

			$error = array('name' => $curl_constant[$curl_code], 'message' => curl_strerror($curl_code));
		}

		$head = '';
		$body = '';

		$parts = explode("\r\n\r\n", $response, 3);

		if (isset($parts[0]) && isset($parts[1])) {
			if (($parts[0] == 'HTTP/1.1 100 Continue') && isset($parts[2])) {
				list($head, $body) = array($parts[1], $parts[2]);
			} else {
				list($head, $body) = array($parts[0], $parts[1]);
			}
		}

		$response_headers = array();
		$header_lines = explode("\r\n", $head);
		array_shift($header_lines);

		foreach ($header_lines as $line) {
			list($key, $value) = explode(':', $line, 2);
			$response_headers[$key] = $value;
		}

		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if (isset($buffer) && is_resource($buffer)) {
			fclose($buffer);
		}

		return array(
			'body'    => $body,
			'headers' => $response_headers,
			'error'   => $error,
			'status'  => $http_status
		);
	}

	/**
	 * Parse API returned error
	 *
	 * @param array $result API result from response.
	 *
	 * @return array|false
	 */
	protected function parseApiError($result) {
		if (!(is_array($result) && isset($result['err']))) {
			return false;
		}

		return array(
			'code'    => isset($result['code']) ? $result['code'] : 0,
			'message' => $result['message'] ?? '',
		);
	}

	/**
	 * Get HTTP response status code
	 *
	 * @return int|string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Get error code from API, HTTP Error Code or JSON error == 1
	 *
	 * @return int
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * Get error message from API, HTTP error body message or JSON parse error
	 *
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->error_message;
	}

	/**
	 * API error check
	 *
	 * @return bool
	 */
	public function hasError() {
		return $this->error_code > 0;
	}

	/**
	 * Format time duration based on thresholds
	 *
	 * @param int $milliseconds The number of milliseconds to format.
	 *
	 * @return string Formatted time.
	 */
	public function formatTimeDuration($milliseconds) {
		if ($milliseconds < 1000) {
			return sprintf('%d ms', $milliseconds);
		}

		$total_seconds = $milliseconds / 1000;

		if ($total_seconds < 60) {
			return sprintf('%.1f s', $total_seconds);
		}

		$minutes = floor($total_seconds / 60);
		$seconds_remainder = $total_seconds % 60;

		return sprintf('%d min %.3f s', $minutes, $seconds_remainder);
	}
}
