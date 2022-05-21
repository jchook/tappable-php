<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

use Tap\Smtp\Element\Origin\Origin;

class Mailbox
{
	public function __construct(
		public string $localPart,
		public Origin $origin,
	)
	{
	}
}
