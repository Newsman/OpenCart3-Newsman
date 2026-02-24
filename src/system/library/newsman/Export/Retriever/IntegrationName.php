<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Integration Name
 *
 * @class \Newsman\Export\Retriever\IntegrationName
 */
class IntegrationName extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process integration name retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		return array('name' => 'newsman');
	}
}
