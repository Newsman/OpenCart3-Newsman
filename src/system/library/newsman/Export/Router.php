<?php

namespace Newsman\Export;

/**
 * Class Export Request
 *
 * @class \Newsman\Export\Router
 */
class Router extends \Newsman\Nzmbase {
	/**
	 * Export data action.
	 * Used by newsman.app to fetch data from a store.
	 *
	 * @return void
	 * @throws \Exception Throws standard exception on errors.
	 */
	public function execute() {
		$export_request = new \Newsman\Export\Request($this->registry);

		// Check for API v1 JSON payload before the legacy query-string check.
		$raw_body = (string) file_get_contents('php://input');
		$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
		$v1_parser = new \Newsman\Export\V1\PayloadParser();
		if ($v1_parser->isV1Payload($raw_body, $content_type)) {
			$this->executeV1($raw_body, $export_request->getStoreId());
			return;
		}

		if (!$export_request->isExportRequest()) {
			return;
		}
		$store_id = $export_request->getStoreId();

		if (!$this->config->isEnabledWithApi()) {
			$result = array(
				'status'  => 403,
				'message' => 'API setting is not enabled in plugin',
			);
			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		}

		try {
			$parameters = $export_request->getRequestParameters();
			$processor = new \Newsman\Export\Retriever\Processor($this->registry);
			$result = $processor->process(
				$processor->getCodeByData($parameters),
				$store_id,
				$parameters
			);

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		} catch (\OutOfBoundsException $e) {
			$this->logger->logException($e);
			$result = array(
				'status'  => 403,
				'message' => $e->getMessage(),
			);

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			$result = array(
				'status'  => 0,
				'message' => $e->getMessage(),
			);

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		}
	}

	/**
	 * Handle an API v1 JSON payload request.
	 *
	 * Parses the JSON body, authenticates via the Bearer token, dispatches to
	 * the appropriate retriever, and renders a JSON response. Errors are returned
	 * as {"error": {"code": <int>, "message": "<string>"}} with the matching
	 * HTTP status code.
	 *
	 * @param string   $raw_body Raw HTTP request body.
	 * @param null|int $store_id Store ID resolved from the request context.
	 *
	 * @return void
	 */
	protected function executeV1($raw_body, $store_id) {
		$renderer = new \Newsman\Export\Renderer($this->registry);

		// Extract Bearer token from the Authorization header.
		$api_key = '';
		$auth = '';
		if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
			$auth = $_SERVER['HTTP_AUTHORIZATION'];
		}
		if (empty($auth) && function_exists('getallheaders')) {
			foreach (getallheaders() as $name => $value) {
				if (strtolower($name) === 'authorization') {
					$auth = $value;
					break;
				}
			}
		}
		if (!empty($auth)) {
			if (stripos($auth, 'Bearer') !== false) {
				$api_key = trim(str_ireplace('Bearer', '', $auth));
			} else {
				$api_key = trim($auth);
			}
		}

		try {
			$v1_parser = new \Newsman\Export\V1\PayloadParser();
			$parsed = $v1_parser->parse($raw_body);

			$code = $parsed['code'];
			$data = $parsed['data'];

			// Override store_id from JSON params if provided.
			if (!empty($data['store_id'])) {
				$store_id = (int) $data['store_id'];
			}

			// Inject API key so the Processor authenticator can validate it.
			if (!empty($api_key)) {
				$data[\Newsman\Export\Retriever\Authenticator::API_KEY_PARAM] = $api_key;
			}

			$processor = new \Newsman\Export\Retriever\Processor($this->registry);
			$result = $processor->process($code, $store_id, $data);

			$renderer->displayJson($result);
		} catch (\Newsman\Export\V1\ApiV1Exception $e) {
			$this->logger->logException($e);
			http_response_code($e->getHttpStatus());
			$renderer->displayJson(array(
				'error' => array(
					'code'    => $e->getErrorCode(),
					'message' => $e->getMessage(),
				),
			));
		} catch (\OutOfBoundsException $e) {
			$this->logger->logException($e);
			http_response_code(403);
			$renderer->displayJson(array(
				'error' => array(
					'code'    => 1001,
					'message' => 'Authentication failed',
				),
			));
		} catch (\Exception $e) {
			$this->logger->logException($e);
			http_response_code(500);
			$renderer->displayJson(array(
				'error' => array(
					'code'    => 1009,
					'message' => 'Internal server error',
				),
			));
		}
	}
}
