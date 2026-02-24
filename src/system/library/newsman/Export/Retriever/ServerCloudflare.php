<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Server Cloudflare
 *
 * @class \Newsman\Export\Retriever\ServerCloudflare
 */
class ServerCloudflare extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process server cloudflare retriever
	 *
	 * Returns true if the current request passed through Cloudflare's proxy
	 * network, detected via the CF-Ray header that Cloudflare attaches to
	 * every proxied request.
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$cloudflare = !empty($_SERVER['HTTP_CF_RAY']);

		return array('cloudflare' => $cloudflare);
	}
}
