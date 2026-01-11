<?php

namespace Newsman\Util;

/**
 * Util functions for clean log
 *
 * @class \Newsman\Util\CleanLog
 */
class CleanLog extends \Newsman\Nzmbase {
	/**
	 * Clean newsman logs
	 *
	 * @return void
	 */
	public function cleanLogs() {
		if (!defined('DIR_LOGS')) {
			return;
		}

		$files = glob(DIR_LOGS . 'newsman_*.log');

		if ($files) {
			$now = time();
			$day = 86400;
			$days_to_keep = $this->config->getDeveloperLogCleanDays();

			foreach ($files as $file) {
				if (is_file($file)) {
					$filename = basename($file);

					if (preg_match('/newsman_(\d{4}-\d{2}-\d{2})\.log/', $filename, $matches)) {
						$file_date = $matches[1];
						$file_time = strtotime($file_date);

						// Keep logs for n days
						if ($file_time && ($now - $file_time >= $days_to_keep * $day)) {
							@unlink($file);
						}
					}
				}
			}
		}
	}
}
