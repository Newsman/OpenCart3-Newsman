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
		$js = '_nzm.identify({email: "' . $this->escapeJs($customer->getEmail()) . '", ' .
			'first_name: "' . $this->escapeJs($customer->getFirstName()) . '", ' .
			'last_name: "' . $this->escapeJs($customer->getLastName()) . '"});';
		$this->getEvent()->trigger('newsmanremarketing/remarketing_action_customer_identify/after', array(&$js));

		return $js;
	}
}
