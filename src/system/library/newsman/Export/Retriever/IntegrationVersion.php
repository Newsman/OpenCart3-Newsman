<?php

namespace Newsman\Export\Retriever;

use Newsman\Util\Version;

/**
 * Class Export Retriever Integration Version
 *
 * @class \Newsman\Export\Retriever\IntegrationVersion
 */
class IntegrationVersion extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process integration version retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$version = new Version($this->registry);

		return array('version' => $version->getVersion());
	}
}
