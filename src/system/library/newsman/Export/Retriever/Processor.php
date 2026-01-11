<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Processor
 *
 * @class \Newsman\Export\Retriever\Processor
 */
class Processor extends \Newsman\Nzmbase {
	/**
	 * Retriever pool
	 *
	 * @var Pool
	 */
	protected $pool;

	/**
	 * Retriever authenticator
	 *
	 * @var Authenticator
	 */
	protected $authenticator;

	/**
	 * Class constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);
		$this->pool = new Pool($registry);
		$this->authenticator = new Authenticator($registry);
	}

	/**
	 * Process retriever data
	 *
	 * @param string   $code Code of retriever.
	 * @param null|int $store_id
	 * @param array    $data Data to filter entities, to save entities, other.
	 *
	 * @return array
	 * @throws \OutOfBoundsException Authentication error. Invalid credentials.
	 */
	public function process($code, $store_id = null, $data = array()) {
		$this->logger->info(
			sprintf(
				'Processing fetch data (%s) for store ID %d.',
				$code,
				$store_id
			)
		);

		$tmp_data = $data;
		unset($tmp_data[Authenticator::API_KEY_PARAM]);
		$this->logger->info(json_encode($tmp_data));
		unset($tmp_data);

		try {
			$api_key = $this->getApiKeyFromData($code, $data);
			$this->authenticator->authenticate($api_key, $store_id);
		} catch (\OutOfBoundsException $e) {
			$this->logger->logException($e);
			throw $e;
		}

		$retriever = $this->pool->getRetrieverByCode($code, $data);
		unset($data[Authenticator::API_KEY_PARAM]);

		// if ($retriever instanceof \Newsman\Export\Retriever\Config) {
		//     $retriever->setRequestApyKey($api_key);
		// }

		return $retriever->process($data, $store_id);
	}

	/**
	 * Get an API key or authentication token key from request data
	 *
	 * @param string $code Retriever code.
	 * @param array  $data Request data.
	 *
	 * @return string
	 */
	protected function getApiKeyFromData($code, $data) {
		if (!empty($data[Authenticator::API_KEY_PARAM])) {
			return $data[Authenticator::API_KEY_PARAM];
		}

		return '';
	}

	/**
	 * Get retriever code from request data
	 *
	 * @param array $data Request data.
	 *
	 * @return false|string
	 */
	public function getCodeByData($data) {
		if (!(isset($data['newsman']) && !empty($data['newsman']))) {
			return false;
		}

		return str_replace('.json', '', $data['newsman']);
	}
}
