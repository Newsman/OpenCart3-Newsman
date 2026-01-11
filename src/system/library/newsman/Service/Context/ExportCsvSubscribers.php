<?php

namespace Newsman\Service\Context;

/**
 * Class Service Context Export CSV subscribers
 *
 * @class \Newsman\Service\Context\ExportCsvSubscribers
 */
class ExportCsvSubscribers extends Store {
	/**
	 * CSV data
	 *
	 * @var array
	 */
	protected $csv_data;

	/**
	 * Store IDs
	 *
	 * @var array
	 */
	protected $store_ids = array();

	/**
	 * Additional fields
	 *
	 * @var array
	 */
	protected $additional_fields = array();

	/**
	 * Set CSV data
	 *
	 * @param array $data CSV data.
	 *
	 * @return $this
	 */
	public function setCsvData($data) {
		$this->csv_data = $data;

		return $this;
	}

	/**
	 * Get CSV data
	 *
	 * @return array
	 */
	public function getCsvData() {
		return $this->csv_data;
	}

	/**
	 * Set Store IDs
	 *
	 * @param array $store_ids Store IDs.
	 *
	 * @return $this
	 */
	public function setStoreIds($store_ids) {
		$this->store_ids = $store_ids;

		return $this;
	}

	/**
	 * Get store IDs
	 *
	 * @return array
	 */
	public function getStoreIds() {
		return $this->store_ids;
	}

	/**
	 * Set additional fields
	 *
	 * @param array $data Additional fields.
	 *
	 * @return $this
	 */
	public function setAdditionalFields($data) {
		$this->additional_fields = $data;

		return $this;
	}

	/**
	 * Get additional fields
	 *
	 * @return array
	 */
	public function getAdditionalFields() {
		return $this->additional_fields;
	}
}
