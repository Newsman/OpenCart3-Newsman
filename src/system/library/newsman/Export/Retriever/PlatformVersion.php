<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Platform Version
 *
 * @class \Newsman\Export\Retriever\PlatformVersion
 */
class PlatformVersion extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process platform version retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		return array('version' => VERSION);
	}
}
