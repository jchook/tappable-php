<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

use Stringable;

class Verb implements Stringable
{
	public function __construct(
		private string $verb
	)
	{
	}

	public function toString()
	{
		return $this->verb;
	}

	public function toUppercase()
	{
		return strtoupper($this->verb);
	}

	public function __toString()
	{
		return $this->verb;
	}
}

