<?php

namespace Newsman\Export\Retriever;

/**
 * Class Export Retriever Coupons
 *
 * @class Coupons
 */
class Coupons extends AbstractRetriever implements RetrieverInterface {
	/**
	 * Process coupons retriever
	 *
	 * @param array    $data Data to filter entities, to save entities, other.
	 * @param null|int $store_id
	 *
	 * @return array
	 */
	public function process($data = array(), $store_id = null) {
		$this->logger->info(sprintf('Add coupons: %s', json_encode($data)));

		$discount_type = !isset($data['type']) ? -1 : (int)$data['type'];
		$value = !isset($data['value']) ? -1 : (int)$data['value'];
		$batch_size = !isset($data['batch_size']) ? 1 : (int)$data['batch_size'];
		$prefix = !isset($data['prefix']) ? '' : $data['prefix'];
		$expire_date = isset($data['expire_date']) ? $data['expire_date'] : null;
		$min_amount = !isset($data['min_amount']) ? -1 : (float)$data['min_amount'];
		$currency = isset($data['currency']) ? $data['currency'] : '';

		if (-1 === $discount_type) {
			return array(
				'status' => 0,
				'msg'    => 'Missing type param',
			);
		} elseif (-1 === $value) {
			return array(
				'status' => 0,
				'msg'    => 'Missing value param',
			);
		}

		try {
			$coupons_codes = array();
			for ($step = 0; $step < $batch_size; $step++) {
				$coupon_code = $this->processCoupon($discount_type, $prefix, $expire_date, $value, $min_amount);
				$coupons_codes[] = $coupon_code;
			}

			$this->logger->info(
				sprintf(
					'Added %d coupons %s',
					count($coupons_codes),
					implode(', ', $coupons_codes)
				)
			);

			return array(
				'status' => 1,
				'codes'  => $coupons_codes,
			);
		} catch (\Exception $e) {
			$this->logger->logException($e);

			return array(
				'status' => 0,
				'msg'    => $e->getMessage(),
			);
		}
	}

	/**
	 * Save coupon
	 *
	 * @param int         $discount_type Discount type 0 or 1.
	 * @param string      $prefix Prefix of coupon code.
	 * @param null|string $expire_date Expire date of coupon code.
	 * @param int         $value Value of discount applied.
	 * @param int         $min_amount Minimum purchase amount.
	 *
	 * @return string
	 */
	public function processCoupon($discount_type, $prefix, $expire_date, $value, $min_amount) {
		$full_coupon_code = $this->generateCouponCode($prefix);

		$coupon_data = array(
			'name'          => 'Generated Coupon ' . $full_coupon_code,
			'code'          => $full_coupon_code,
			'discount'      => $value,
			'type'          => ($discount_type == 1) ? 'P' : 'F',
			'total'         => ($min_amount != -1) ? $min_amount : 0,
			'logged'        => 0,
			'shipping'      => 0,
			'date_start'    => date('Y-m-d'),
			'date_end'      => ($expire_date != null) ? date('Y-m-d', strtotime($expire_date)) : date('Y-m-d', strtotime('+5 year')),
			'uses_total'    => 1,
			'uses_customer' => 1,
			'status'        => 1
		);

		$this->event->trigger('newsman/export_retriever_coupons_process_coupon/before', array(&$coupon_data));

		$this->registry->db->query("INSERT INTO " . DB_PREFIX . "coupon SET " .
			"name = '" . $this->registry->db->escape($coupon_data['name']) . "', " .
			"code = '" . $this->registry->db->escape($coupon_data['code']) . "', " .
			"discount = '" . (float)$coupon_data['discount'] . "', " .
			"type = '" . $this->registry->db->escape($coupon_data['type']) . "', " .
			"total = '" . (float)$coupon_data['total'] . "', " .
			"logged = '" . (int)$coupon_data['logged'] . "', " .
			"shipping = '" . (int)$coupon_data['shipping'] . "', " .
			"date_start = '" . $this->registry->db->escape($coupon_data['date_start']) . "', " .
			"date_end = '" . $this->registry->db->escape($coupon_data['date_end']) . "', " .
			"uses_total = '" . (int)$coupon_data['uses_total'] . "', " .
			"uses_customer = '" . (int)$coupon_data['uses_customer'] . "', " .
			"status = '" . (int)$coupon_data['status'] . "'");

		return $full_coupon_code;
	}

	/**
	 * Generate coupon code
	 *
	 * @param string $prefix Prefix of coupon code.
	 *
	 * @return string
	 */
	public function generateCouponCode($prefix) {
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$fail_safe = 0;

		do {
			++$fail_safe;
			$coupon_code = '';
			for ($i = 0; $i < 8; $i++) {
				$coupon_code .= $characters[rand(0, strlen($characters) - 1)];
			}
			$full_coupon_code = $prefix . $coupon_code;

			/** @var \stdClass $query */
			$query = $this->registry->db->query("SELECT coupon_id FROM " . DB_PREFIX . "coupon WHERE code = '" . $this->registry->db->escape($full_coupon_code) . "'");
			$existing_coupon_id = $query->num_rows > 0;
		} while (!empty($existing_coupon_id) && $fail_safe < 3);

		return $full_coupon_code;
	}
}
