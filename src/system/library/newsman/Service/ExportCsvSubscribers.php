<?php

namespace Newsman\Service;

/**
 * API Class Service Export Csv Subscribers
 *
 * @class \Newsman\Service\ExportCsvSubscribers
 */
class ExportCsvSubscribers extends AbstractService {
	/**
	 * Export CSV with subscribers to the Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/import.csv
	 */
	public const ENDPOINT = 'import.csv';

	/**
	 * Export CSV subscribers
	 *
	 * @param Context\ExportCsvSubscribers $context Export CSV subscribers context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		$api_context = $this->createApiContext()
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Try to export CSV with %s subscribers'),
				count($context->getCsvData())
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_export_csv_subscribers/before', array($context));
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'  => $context->getListId(),
				'segments' => (!empty($context->getSegmentId())) ? array($context->getSegmentId()) : $context->getNullValue(),
				'csv_data' => $this->serializeCsvData($context),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Sent export CSV with %s subscribers'),
				count($context->getCsvData())
			)
		);

		return $result;
	}

	/**
	 * Create a CSV file format and return it.
	 *
	 * @param Context\ExportCsvSubscribers $context Context.
	 * @param string                       $source Source column value.
	 *
	 * @return string
	 */
	public function serializeCsvData($context, $source = 'OpenCart3') {
		$header = $this->getCsvHeader($context);
		$column_count = count($header);
		$csv_data = $context->getCsvData();
		$additional_fields = $context->getAdditionalFields();

		$csv = '"' . implode('","', $this->getCsvHeader($context)) . "\"\n";
		foreach ($csv_data as $key => $row) {
			$export_row = array_combine($header, array_fill(0, $column_count, ''));
			foreach ($row as $column => &$value) {
				if ('additional' !== $column) {
					if (null === $value) {
						$value = '';
					}
					$value = trim(str_replace('"', '', $value));
				} elseif (null === $value) {
					$value = array();
				}
			}
			$row['source'] = $source;

			foreach ($additional_fields as $attribute) {
				$row[$attribute] = '';
				if (isset($row['additional'][$attribute])) {
					$row[$attribute] = $row['additional'][$attribute];
				}
			}

			foreach ($export_row as $export_key => &$export_value) {
				if (isset($row[$export_key])) {
					$export_value = $row[$export_key];
				}
			}

			$csv .= $this->getCsvLine($export_row, $key);
		}

		return $csv;
	}

	/**
	 * Get CSV header
	 *
	 * @param Context\ExportCsvSubscribers $context Context.
	 *
	 * @return array
	 */
	public function getCsvHeader($context) {
		$header = array(
			'email',
			'firstname',
			'lastname',
		);

		if ($this->config->isSendTelephone()) {
			$header[] = 'tel';
			$header[] = 'phone';
			$header[] = 'telephone';
			$header[] = 'billing_telephone';
			$header[] = 'shipping_telephone';
		}

		$header[] = 'source';

		foreach ($context->getAdditionalFields() as $attribute) {
			if (!in_array($attribute, $header, true)) {
				$header[] = $attribute;
			}
		}

		return $header;
	}

	/**
	 * Get CSV line
	 *
	 * @param array $row CSV data row.
	 * @param int   $key Index key.
	 *
	 * @return string
	 */
	public function getCsvLine($row, $key) {
		unset($row['additional']);

		return '"' . implode('","', $row) . "\"\n";
	}
}
