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
		$script_js = $this->getConfig()->getScriptJs();
		$this->event->trigger('newsmanremarketing/script_track_get_script_js/after', array(&$script_js));

		// The script tag is not present in the script, something went wrong.
		if (stripos($script_js, '<script') === false) {
			return '';
		}

		$nzm_config_js = "_nzm_config['disable_datalayer'] = 1;";
		$nzm_config_js .= $this->getJsConfig();

		$output = '';

		if (!empty($nzm_config_js)) {
			$output .= '<script' . $this->getScriptTagAdditionalAttributes() . '>';
			$output .= 'var _nzm_config = _nzm_config || [];';
			$output .= $nzm_config_js;
			$output .= '</script>';
		}

		$script_js = str_replace(
			'<script',
			'<script ' . $this->escapeHtml($this->getScriptTagAdditionalAttributes()) . ' ',
			$script_js
		);

		// The script tag is not present in the script, something went wrong.
		if (stripos($script_js, '<script') === false) {
			return '';
		}

		$output .= $script_js;

		return $output;
	}

	/**
	 * Get script tag additional attributes.
	 * Example: type="text/plain" used for GDPR scripts blocking cookies.
	 *
	 * @return string
	 */
	public function getScriptTagAdditionalAttributes() {
		$attributes = '';
		$this->event->trigger(
			'newsmanremarketing/script_tracking_attributes/before',
			array(&$attributes)
		);

		return $attributes;
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
	 * @deprecated No longer used. Remarketing script is fetched from Newsman API.
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
	 *
	 * @return string
	 * @deprecated No longer used. Remarketing script is fetched from Newsman API.
	 */
	public function getResourcesUrl() {
		return '';
	}

	/**
	 * Get script request uri.
	 *
	 * @return string
	 * @deprecated No longer used. Remarketing script is fetched from Newsman API.
	 */
	public function getScriptRequestUri() {
		return '';
	}
}
