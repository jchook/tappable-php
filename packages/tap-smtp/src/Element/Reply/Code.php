<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

class Code
{
	public function __construct(
		public int $value,
	)
	{
	}

	public function isFail(): bool
	{
		return $this->value >= 400;
	}

	public function isPermFail(): bool
	{
		return $this->value >= 500;
	}

	public function isTempFail(): bool
	{
		return $this->value >= 400 && $this->value < 500;
	}

	public function isSuccess(): bool
	{
		return $this->value >= 200 && $this->value < 300;
	}
}


