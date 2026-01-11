<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Factory
 *
 * @class \Newsman\Export\Retriever\Authenticator
 */
class Authenticator extends \Newsman\Nzmbase {
	/**
	 * API key request parameter
	 */
	public const API_KEY_PARAM = 'nzmhash';

	/**
	 * Authenticate incoming Newsman export request
	 *
	 * @param string   $api_key Newsman API key.
	 * @param null|int $store_id
	 *
	 * @return true
	 * @throws \OutOfBoundsException Invalid API key.
	 */
	public function authenticate($api_key, $store_id = null) {
		if (empty($api_key)) {
			throw new \OutOfBoundsException('Empty API key provided.');
		}

		$config_api_key = $this->config->getApiKey($store_id);
		$config_auth_token = $this->config->getAuthenticateToken($store_id);

		$alternate_name = $this->config->getExportAuthorizeHeaderName($store_id);
		$alternate_key = $this->config->getExportAuthorizeHeaderKey($store_id);
		$is_alternate = false;
		if (!empty($alternate_name) && !empty($alternate_key)) {
			$is_alternate = true;
		}

		$is_alternate_with_token = false;
		if (!empty($config_auth_token)) {
			$is_alternate_with_token = true;
		}

		$is_authenticated = false;
		if ($config_api_key === $api_key) {
			$is_authenticated = true;
		}
		if ($is_alternate_with_token && ($config_auth_token === $api_key)) {
			$is_authenticated = true;
		}
		if ($is_alternate && ($alternate_key === $api_key)) {
			$is_authenticated = true;
		}

		if (!$is_authenticated) {
			throw new \OutOfBoundsException(
				sprintf('Invalid API key for store ID %d', $store_id)
			);
		}

		return true;
	}
}
