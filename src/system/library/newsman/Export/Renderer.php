<?php

namespace Newsman\Export;

/**
 * Retriever renderer
 *
 * @class \Newsman\Export\Renderer
 */
class Renderer extends \Newsman\Nzmbase {
	/**
	 * Encode array or object and set headers.
	 *
	 * @param array|Object $data Array or object to encode.
	 *
	 * @return void
	 */
	public function displayJson($data) {
		header('Content-Type: application/json');

		// Disable caching.
		header('Pragma: no-cache');

		header('Expires: Wed, 11 Jan 1994 05:00:00 GMT');
		header('Cache-Control: no-cache, must-revalidate, max-age=0, no-store, private');
		// Old IE headers to prevent caching. Remove in the future.
		header('Cache-Control: post-check=0, pre-check=0', false);

		header_remove('Last-Modified');

		echo json_encode($data);
		exit(0);
	}
}
