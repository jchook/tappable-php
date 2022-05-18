<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class Mailbox
{
	public function __construct(
		public string $localPart,
		public string $domain
	)
	{
	}
}
