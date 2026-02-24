<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Platform Language Version
 *
 * @class \Newsman\Export\Retriever\PlatformLanguageVersion
 */
class PlatformLanguageVersion extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process platform language version retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		return array('language_version' => phpversion());
	}
}
