<?php

namespace Newsman\Remarketing\Script;

/**
 * Class Track
 */
class Track extends \Newsman\Nzmbase {
	/**
	 * @var string
	 */
	protected $js_config = '';

	/**
	 * Get the tracking script
	 *
	 * @return string
	 */
	public function getScript() {
		$condition_tunnel_script = 'false';
		$resources_base_url = '';
		$tracking_base_url = '';

		if ($this->getConfig()->useProxy()) {
			$condition_tunnel_script = 'true';
			$resources_base_url = $this->escapeJs($this->escapeHtml($this->getResourcesUrl()));
			$tracking_base_url = $this->escapeJs($this->escapeHtml($this->getScriptRequestUri()));
		}

		$nzm_js_config = "_nzm_config['disable_datalayer'] = 1;";
		$nzm_js_config .= $this->getJsConfig();

		$script_js = strtr(
			$this->getConfig()->getScriptJs(),
			array(
				'{{nzmConfigJs}}'           => $nzm_js_config,
				'{{conditionTunnelScript}}' => $condition_tunnel_script,
				'{{resourcesBaseUrl}}'      => $resources_base_url,
				'{{trackingBaseUrl}}'       => $tracking_base_url,
				'{{remarketingId}}'         => $this->escapeHtml($this->getConfig()->getRemarketingId()),
				'{{trackingScriptUrl}}'     => $this->escapeHtml($this->getScriptFinalUrl())
			)
		);

		return $script_js;
	}

	/**
	 * Set config JS
	 *
	 * @param string $js
	 */
	public function setJsConfig($js) {
		$this->js_config = $js;
	}

	/**
	 * Get config JS
	 *
	 * @return string
	 */
	public function getJsConfig() {
		return $this->js_config;
	}

	/**
	 * Get tracking script final URL
	 *
	 * @return string
	 * @throws \Exception Not implemented yet exception.
	 */
	public function getScriptFinalUrl() {
		$url = '';
		if ($this->getConfig()->useProxy()) {
			$url = $this->getResourcesUrl() . '/' . $this->getScriptRequestUri();
			throw new \Exception('Not implemented');
		} else {
			$url = $this->getConfig()->getScriptUrl();
		}

		return $url;
	}

	/**
	 * Get resources URL.
	 * It will be implemented soon.
	 *
	 * @return string
	 */
	public function getResourcesUrl() {
		return '';
	}

	/**
	 * Get script request uri.
	 * It will be implemented soon.
	 *
	 * @return string
	 */
	public function getScriptRequestUri() {
		return '';
	}
}
