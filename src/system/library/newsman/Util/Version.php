<?php

namespace Newsman\Util;

class Version extends \Newsman\Nzmbase {
	/**
	 * @return string
	 */
	public function getVersion() {
		$composer_path = dirname(__DIR__, 5) . '/composer.json';
		if (file_exists($composer_path)) {
			$composer = json_decode(file_get_contents($composer_path), true);
			if (isset($composer['version'])) {
				return $composer['version'];
			}
		}

		return '0.0.0';
	}
}
