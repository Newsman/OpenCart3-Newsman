<?php

namespace Newsman;

/**
 * Autoload Newsman classes
 */
class Nzmloader extends \Newsman\Library {
	public function autoload() {
		$filepath = __DIR__ . '/vendor/autoload.php';
		if (file_exists($filepath)) {
			require_once $filepath;
		}
	}
}
