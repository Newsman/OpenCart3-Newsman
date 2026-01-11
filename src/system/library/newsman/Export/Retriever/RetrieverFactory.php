<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Factory
 *
 * @class \Newsman\Export\Retriever\RetrieverFactory
 */
class RetrieverFactory extends \Newsman\Nzmbase {
	/**
	 * Create retriever instance
	 *
	 * @param string $class_name Class name of retriever.
	 * @param array  $data Data to pass in retriever constructor.
	 *
	 * @return RetrieverInterface
	 * @throws \InvalidArgumentException Invalid retriever class.
	 */
	public function create($class_name, $data = array()) {
		if (!class_exists($class_name)) {
			throw new \InvalidArgumentException('Type "' . $class_name . '" does not exist.');
		}

		$instance = new $class_name($this->registry);

		if (!$instance instanceof RetrieverInterface) {
			throw new \InvalidArgumentException(
				'Type "' . $class_name . '" is not instance on ' . RetrieverInterface::class
			);
		}

		return $instance;
	}
}
