<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Pool
 *
 * @class \Newsman\Export\Retriever\Pool
 */
class Pool extends \Newsman\Nzmbase {
	/**
	 * Configuration list of retriever
	 *
	 * @var array
	 */
	protected $retriever_list = array(
		'coupons'          => array(
			'code'  => 'coupons',
			'class' => '\Newsman\Export\Retriever\Coupons'
		),
		'cron-orders' => array(
			'code'  => 'cron-orders',
			'class' => '\Newsman\Export\Retriever\CronOrders'
		),
		'cron-subscribers' => array(
			'code'  => 'cron-subscribers',
			'class' => '\Newsman\Export\Retriever\CronSubscribers'
		),
		'customers'        => array(
			'code'  => 'customers',
			'class' => '\Newsman\Export\Retriever\Customers'
		),
		'orders'           => array(
			'code'  => 'orders',
			'class' => '\Newsman\Export\Retriever\Orders'
		),
		'products'         => array(
			'code'  => 'products',
			'class' => '\Newsman\Export\Retriever\Products'
		),
		'send-orders'      => array(
			'code'  => 'send-orders',
			'class' => '\Newsman\Export\Retriever\SendOrders'
		),
		'send-subscribers' => array(
			'code'  => 'send-subscribers',
			'class' => '\Newsman\Export\Retriever\SendSubscribers'
		),
		'subscribers'      => array(
			'code'  => 'subscribers',
			'class' => '\Newsman\Export\Retriever\Subscribers'
		),
		'version'          => array(
			'code'  => 'version',
			'class' => '\Newsman\Export\Retriever\Version'
		)
	);

	/**
	 * Retriever instances list
	 *
	 * @var array
	 */
	protected $retriever_instances = array();

	/**
	 * Retriever factory
	 *
	 * @var RetrieverFactory
	 */
	protected $factory;

	/**
	 * Class construct
	 */
	public function __construct($registry) {
		parent::__construct($registry);
		$this->factory = new RetrieverFactory($registry);
	}

	/**
	 * Get retriever list
	 *
	 * @return array
	 */
	public function getRetrieverList() {
		$this->event->trigger('newsman/export_retriever_pool_get_retriever_list/before', array(&$this->retriever_list));

		return $this->retriever_list;
	}

	/**
	 * Set a retrievers list
	 *
	 * @param array $retriever_list List with new retrievers.
	 *
	 * @return self
	 */
	public function setRetrieverList($retriever_list) {
		$this->retriever_list = $retriever_list;

		return $this;
	}

	/**
	 * Get retriever by code instantiated
	 *
	 * @param string $code Code of retriever.
	 * @param array  $data Request data parameters.
	 *
	 * @return RetrieverInterface
	 * @throws \InvalidArgumentException Throws invalid argument code retriever exception.
	 */
	public function getRetrieverByCode($code, $data) {
		$code = strtolower($code);

		if (isset($this->retriever_instances[$code])) {
			return $this->retriever_instances[$code];
		}

		foreach ($this->getRetrieverList() as $retriever) {
			if ($retriever['code'] === $code) {
				if (empty($retriever['class'])) {
					throw new \InvalidArgumentException('The parameter "class" is missing.');
				}

				$this->retriever_instances[$code] = $this->factory->create($retriever['class']);
				break;
			}
		}

		if (!isset($this->retriever_instances[$code])) {
			throw new \InvalidArgumentException('The parameter "code" is missing.');
		}

		return $this->retriever_instances[$code];
	}
}
