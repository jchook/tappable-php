<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

/**
 *
 */
class GenericReply implements Reply
{
	public function __construct(
		public Code $code,
		public array $messages
	)
	{
	}

	public function getCode(): Code
	{
		return $this->code;
	}
}

