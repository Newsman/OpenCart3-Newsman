<?php

namespace Newsman\Export;

/**
 * Class Export Request
 *
 * @class \Newsman\Export\Router
 */
class Router extends \Newsman\Nzmbase {
	/**
	 * Export data action.
	 * Used by newsman.app to fetch data from a store.
	 *
	 * @return void
	 * @throws \Exception Throws standard exception on errors.
	 */
	public function execute() {
		$export_request = new \Newsman\Export\Request($this->registry);
		if (!$export_request->isExportRequest()) {
			return;
		}
		$store_id = $export_request->getStoreId();

		if (!$this->config->isEnabledWithApi()) {
			$result = array(
				'status'  => 403,
				'message' => 'API setting is not enabled in plugin',
			);
			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		}

		try {
			$parameters = $export_request->getRequestParameters();
			$processor = new \Newsman\Export\Retriever\Processor($this->registry);
			$result = $processor->process(
				$processor->getCodeByData($parameters),
				$store_id,
				$parameters
			);

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		} catch (\OutOfBoundsException $e) {
			$this->logger->logException($e);
			$result = array(
				'status'  => 403,
				'message' => $e->getMessage(),
			);

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		} catch (\Exception $e) {
			$this->logger->logException($e);
			$result = array(
				'status'  => 0,
				'message' => $e->getMessage(),
			);

			$renderer = new \Newsman\Export\Renderer($this->registry);
			$renderer->displayJson($result);
		}
	}
}
