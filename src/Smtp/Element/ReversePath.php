<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class ReversePath implements Path
{
	public function __construct(
		public ?Mailbox $mailbox
	)
	{
	}

	public function isNull(): bool
	{
		return is_null($this->mailbox);
	}
}

