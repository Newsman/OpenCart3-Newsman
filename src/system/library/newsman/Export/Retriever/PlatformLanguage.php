<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Platform Language
 *
 * @class \Newsman\Export\Retriever\PlatformLanguage
 */
class PlatformLanguage extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process platform language retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		return array('language' => 'PHP');
	}
}
