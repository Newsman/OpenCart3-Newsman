<?php

namespace Newsman\User;

/**
 * Get user host / server IP address
 *
 * @class \Newsman\User\HostIpAddress
 */
class HostIpAddress extends \Newsman\Nzmbase {
	/**
	 * Not found value
	 */
	public const NOT_FOUND = 'not found';

	/**
	 * @var \ModelExtensionNewsmanSetting
	 */
	protected $setting;

	/**
	 * IP address
	 *
	 * @var string|null
	 */
	protected $ip;

	/**
	 * Class construct
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->registry->load->model('extension/newsman/setting');
		$this->setting = $this->registry->get('model_extension_newsman_setting');
	}

	/**
	 * Get the host IP address.
	 *
	 * @return string The IP address.
	 */
	public function getIp() {
		if (null !== $this->ip) {
			return $this->ip;
		}

		$ip = $this->config->getServerIp();
		if (!empty($ip)) {
			if (self::NOT_FOUND === $ip) {
				$this->ip = '';
			} else {
				$this->ip = $ip;
			}

			return $this->ip;
		}

		$url = $this->getUrl();
		$ip = '';
		if (false !== $url) {
			$ip = $this->lookupIp($url);
		}

		if (empty($ip)) {
			$ip = self::NOT_FOUND;
		}

		$this->setting->editSetting('newsman', array('newsman_server_ip' => $ip), $this->config->getCurrentStoreId());

		if (self::NOT_FOUND === $ip) {
			$this->ip = '';
		} else {
			$this->ip = $ip;
		}

		return $this->ip;
	}

	/**
	 * Fetch am asset URL from the current OpenCart website to get the server IP address.
	 *
	 * @param string $url URL to fetch.
	 *
	 * @return string
	 */
	protected function lookupIp($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_exec($ch);
		$ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
		curl_close($ch);

		if (empty($ip) || '127.0.0.1' === $ip) {
			return '';
		}

		return $ip;
	}

	/**
	 * Get URL to request.
	 *
	 * @return string|false
	 */
	public function getUrl() {
		return $this->getFirstFileFromImages();
	}

	/**
	 * Get the first file (excluding .htaccess and hidden files) from the images directory
	 *
	 * @param string $subdir Optional. Subdirectory within images to search. The default is 'catalog'.
	 *
	 * @return string|false The URL of the first file found, or false if no files were found.
	 */
	protected function getFirstFileFromImages($subdir = 'catalog') {
		if (!defined('DIR_IMAGE')) {
			return false;
		}

		$base_dir = DIR_IMAGE;

		// Append a subdirectory if provided.
		if (!empty($subdir)) {
			$dir_path = rtrim($base_dir, '/') . '/' . trim($subdir, '/') . '/';
		} else {
			$dir_path = rtrim($base_dir, '/') . '/';
		}

		// Check if the directory exists.
		if (!is_dir($dir_path)) {
			return false;
		}

		// Open the directory.
		$dir = opendir($dir_path);
		if (!$dir) {
			return false;
		}

		$fail_safe = 0;
		// Loop through directory entries.
		while ((false !== ($file = readdir($dir))) && (++$fail_safe < 10)) {
			// Skip directories, .htaccess, and other hidden files.
			if (is_dir($dir_path . $file) || '.htaccess' === $file || '.' === $file
				|| '..' === $file || 0 === strpos($file, '.')) {
				continue;
			}

			// Found a file, close the directory and return its URL.
			closedir($dir);

			$server_url = '';
			if ($this->registry->get('request')->server['HTTPS']) {
				$server_url = $this->registry->get('config')->get('config_ssl');
			} else {
				$server_url = $this->registry->get('config')->get('config_url');
			}

			if (empty($server_url)) {
				$server_url = HTTP_SERVER;
			}

			// Convert the file path to URL.
			return rtrim($server_url, '/') . '/image/' . (empty($subdir) ? '' : trim($subdir, '/') . '/') . $file;
		}

		// No files found, close directory.
		closedir($dir);

		return false;
	}
}
