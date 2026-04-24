<?php

namespace Newsman\Util;

class Version extends \Newsman\Nzmbase {
	const VERSION = '3.1.8';

	/**
	 * @return string
	 */
	public function getVersion() {
		return self::VERSION;
	}
}
