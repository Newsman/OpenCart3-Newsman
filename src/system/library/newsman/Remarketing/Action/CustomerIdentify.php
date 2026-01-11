<?php

namespace Newsman\Remarketing\Action;

/**
 * Class CustomerIdentify Action
 */
class CustomerIdentify extends \Newsman\Remarketing\Action\AbstractAction {
	/**
	 * @param \Cart\Customer $customer
	 *
	 * @return string
	 */
	public function getJs($customer) {
		$this->getEvent()->trigger('newsmanremarketing/remarketing_action_customer_identify/before', array(&$customer));
		$js = '_nzm.identify({email: "' . $this->escapeHtml($customer->getEmail()) . '", ' .
			'first_name: "' . $this->escapeHtml($customer->getFirstName()) . '", ' .
			'last_name: "' . $this->escapeHtml($customer->getLastName()) . '"});';
		$this->getEvent()->trigger('newsmanremarketing/remarketing_action_customer_identify/after', array(&$js));

		return $js;
	}
}
