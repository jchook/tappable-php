<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class Param
{
	public function __construct(
		public string $name,
		public ?string $value = null,
	)
	{
	}
}

