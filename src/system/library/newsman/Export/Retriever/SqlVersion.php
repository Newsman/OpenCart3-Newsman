<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever SQL Version
 *
 * @class \Newsman\Export\Retriever\SqlVersion
 */
class SqlVersion extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process SQL version retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$query   = $this->registry->db->query("SELECT VERSION() AS v");
		$full    = isset($query->row['v']) ? (string)$query->row['v'] : '';
		$version = preg_replace('/[-\s].*/', '', $full);

		return array('version' => $version);
	}
}
