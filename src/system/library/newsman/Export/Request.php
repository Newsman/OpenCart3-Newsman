<?php

namespace Newsman\Export;

use Newsman\Export\Retriever\Authenticator;
use Newsman\Export\Retriever\Pool;

/**
 * Class Export Request
 *
 * @class \Newsman\Export\Request
 */
class Request extends \Newsman\Nzmbase {
	/**
	 * Known HTTP GET parameters intercepted
	 *
	 * @var array
	 */
	protected $known_get_parameters = array(
		// Retriever code
		'newsman',
		// Lists export in general
		'start',
		'limit',
		'created_at',
		'modified_at',
		'last-days',
		'sort',
		'order',
		// Subscriber export
		'subscriber_id',
		'subscriber_ids',
		// Customer export
		'customer_id',
		'customer_ids',
		// Orders export
		'order_id',
		'order_ids',
		// Products export
		'product_id',
		'product_ids',
		// Cron
		'cron',
		// Coupons export
		'type',
		'value',
		'batch_size',
		'prefix',
		'expire_date',
		'min_amount',
		'currency',
	);

	/**
	 * Known HTTP POST parameters intercepted
	 *
	 * @var array
	 */
	protected $known_post_parameters = array(
		// Retriever code
		'newsman',
		// Lists export in general
		'start',
		'limit',
		'created_at',
		'modified_at',
		'last-days',
		'sort',
		'order',
		// Subscriber export
		'subscriber_id',
		'subscriber_ids',
		// Customer export
		'customer_id',
		'customer_ids',
		// Orders export
		'order_id',
		'order_ids',
		// Products export
		'product_id',
		'product_ids',
		// Cron
		'cron',
		// Coupons export
		'type',
		'value',
		'batch_size',
		'prefix',
		'expire_date',
		'min_amount',
		'currency',
	);

	/**
	 * @var array
	 */
	protected $cron_params = array(
		'cron' => 'cron-subscribers.json',
		'cron-orders' => 'cron-orders.json'
	);

	/**
	 * @var \Request
	 */
	protected $request;

	/**
	 * @var Pool
	 */
	protected $pool;

	/**
	 * Class construct
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->request = $this->registry->request;
		$this->pool = new Pool($registry);
	}

	/**
	 * Is called Newsman export request
	 *
	 * @return bool
	 */
	public function isExportRequest() {
		$newsman = isset($this->request->get['newsman']) ? $this->request->get['newsman'] : '';
		if (empty($newsman)) {
			$newsman = isset($this->request->post['newsman']) ? $this->request->post['newsman'] : '';
		}

		if (empty($newsman)) {
			foreach ($this->cron_params as $cron_param => $code) {
				if (!empty($this->request->get[$cron_param]) && ($this->request->get[$cron_param] === 'true' || $this->request->get[$cron_param] === '1')) {
					$newsman = $code;
					break;
				} elseif (!empty($this->request->post[$cron_param]) && ($this->request->post[$cron_param] === 'true' || $this->request->post[$cron_param] === '1')) {
					$newsman = $code;
					break;
				}
			}
		}

		return !empty($newsman);
	}

	/**
	 * Get request GET, POST, and API key parameters
	 *
	 * @return array
	 */
	public function getRequestParameters() {
		$parameters = $this->getAllKnownParameters();

		$api_key = $this->getApiKeyFromHeader();
		if (!empty($api_key) && empty($parameters[Authenticator::API_KEY_PARAM])) {
			$parameters[Authenticator::API_KEY_PARAM] = $api_key;
		}

		return $parameters;
	}

	/**
	 * Get retriever parameters
	 *
	 * @return array
	 */
	protected function getRetrieverParameters() {
		$parameters = array();

		foreach ($this->pool->getFiltersRetrievers() as $retriever) {
			$instance = $this->pool->getRetrieverByCode($retriever['code'], array());

			$parameters = array_merge($parameters, array_keys($instance->getWhereParametersMapping()));
			$parameters = array_merge($parameters, array_keys($instance->getAllowedSortFields()));
		}

		return array_unique($parameters);
	}

	/**
	 * Get all known parameters
	 *
	 * @return array
	 */
	protected function getAllKnownParameters() {
		$parameters = array();
		$hash_key = Authenticator::API_KEY_PARAM;

		if (!empty($this->request->get[$hash_key])) {
			$parameters[$hash_key] = $this->request->get[$hash_key];
		} elseif (!empty($this->request->post[$hash_key])) {
			$parameters[$hash_key] = $this->request->post[$hash_key];
		}

		foreach ($this->known_get_parameters as $parameter) {
			if (isset($this->request->get[$parameter])) {
				$parameters[$parameter] = $this->request->get[$parameter];
			}
		}

		foreach ($this->known_post_parameters as $parameter) {
			if (isset($this->request->post[$parameter])) {
				$parameters[$parameter] = $this->request->post[$parameter];
			}
		}

		foreach ($this->getRetrieverParameters() as $parameter) {
			if (isset($this->request->get[$parameter])) {
				$parameters[$parameter] = $this->request->get[$parameter];
			}

			if (isset($this->request->post[$parameter])) {
				$parameters[$parameter] = $this->request->post[$parameter];
			}
		}

		if (!(isset($parameters['store_id']) && $parameters['store_id'] !== null && $parameters['store_id'] !== '')) {
			$parameters['store_id'] = $this->getStoreId();
		}

		foreach ($this->cron_params as $cron_param => $code) {
			if (!empty($this->request->get[$cron_param]) && ($this->request->get[$cron_param] === 'true' || $this->request->get[$cron_param] === '1')) {
				$parameters['newsman'] = $code;
				break;
			} elseif (!empty($this->request->post[$cron_param]) && ($this->request->post[$cron_param] === 'true' || $this->request->post[$cron_param] === '1')) {
				$parameters['newsman'] = $code;
				break;
			}
		}

		$this->event->trigger('newsman/export_request_getAllKnownParameters/after', array(&$parameters));

		return $parameters;
	}

	/**
	 * Get API key from request HTTP headers
	 *
	 * @return string
	 */
	protected function getApiKeyFromHeader() {
		$auth = '';

		if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
			$auth = $_SERVER['HTTP_AUTHORIZATION'];
		}

		if (empty($auth)) {
			$auth = $this->getHeaderValue('authorization');
			if (empty($auth)) {
				$name = $this->config->getExportAuthorizeHeaderName($this->getStoreId());
				if (!empty($name)) {
					$auth = trim((string)$this->getHeaderValue($name));
					if (!empty($auth)) {
						return $auth;
					}
				}

				return '';
			}
		}

		if (stripos($auth, 'Bearer') !== false) {
			return trim(str_ireplace('Bearer', '', $auth));
		}

		return $auth;
	}

	/**
	 * Get store ID
	 *
	 * @return int
	 */
	public function getStoreId() {
		$store_id = null;
		if (!empty($this->request->get['store_id'])) {
			$store_id = $this->request->get['store_id'];
		} elseif (!empty($this->request->post['store_id'])) {
			$store_id = $this->request->post['store_id'];
		}
		if ($store_id == null) {
			$store_id = $this->config->getCurrentStoreId();
		}

		return $store_id;
	}

	/**
	 * Get HTTP header by name
	 *
	 * @param string $name Header name.
	 *
	 * @return false|string
	 */
	protected function getHeaderValue($name) {
		$name = strtolower($name);
		if (function_exists('getallheaders')) {
			$headers = getallheaders();
			foreach ($headers as $a_name => $value) {
				if (strtolower($a_name) === $name) {
					return $value;
				}
			}
		}

		return false;
	}
}
