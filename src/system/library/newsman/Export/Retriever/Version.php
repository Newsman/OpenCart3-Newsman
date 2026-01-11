<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Version
 *
 * @class \Newsman\Export\Retriever\Version
 */
class Version extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process version retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		return array('version' => 'Opencart ' . VERSION);
	}
}
