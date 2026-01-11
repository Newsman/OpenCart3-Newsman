<?php

namespace Newsman\Api\Error;

/**
 * Newsman API errors for endpoint subscriber.initSubscribe
 *
 * @see https://kb.newsman.com/api/1.2/subscriber.initSubscribe
 * @class \Newsman\Api\Error\InitSubscribe
 */
abstract class InitSubscribe extends AbstractError {
	/**
	 * Too many requests for this subscriber. Can only send it once every 10 minutes
	 */
	public const TOO_MANY_REQUESTS = 128;
}
