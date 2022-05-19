<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class ForwardPath implements Path
{
	public function __construct(
		public Mailbox $mailbox
	)
	{
	}
}

