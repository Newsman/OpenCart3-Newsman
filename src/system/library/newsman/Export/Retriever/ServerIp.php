<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Server IP
 *
 * @class \Newsman\Export\Retriever\ServerIp
 */
class ServerIp extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process server IP retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$resolver = new \Newsman\Util\ServerIpResolver();

		return array('ip' => $resolver->resolve());
	}
}
