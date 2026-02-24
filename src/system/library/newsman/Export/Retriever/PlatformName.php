<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Platform Name
 *
 * @class \Newsman\Export\Retriever\PlatformName
 */
class PlatformName extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process platform name retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		return array('name' => 'OpenCart');
	}
}
