<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever SQL Name
 *
 * @class \Newsman\Export\Retriever\SqlName
 */
class SqlName extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process SQL name retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$query = $this->registry->db->query("SELECT VERSION() AS v");
		$full  = isset($query->row['v']) ? (string)$query->row['v'] : '';
		$name  = (stripos($full, 'mariadb') !== false) ? 'MariaDB' : 'MySQL';

		return array('name' => $name);
	}
}
