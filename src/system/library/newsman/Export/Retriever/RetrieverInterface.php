<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever interface
 *
 * @class \Newsman\Export\Retriever\RetrieverInterface
 */
interface RetrieverInterface {
	/**
	 * Process retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null);
}
