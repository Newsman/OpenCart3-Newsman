<?php

namespace Newsman\Remarketing\Action;

/**
 * Class PageView Action
 */
class PageView extends \Newsman\Remarketing\Action\AbstractAction {
	/**
	 * @return string
	 */
	public function getJs() {
		$js = "_nzm.run('send', 'pageview'); ";
		$this->getEvent()->trigger('newsmanremarketing/remarketing_action_page_view/after', array(&$js));
		return $js;
	}
}
