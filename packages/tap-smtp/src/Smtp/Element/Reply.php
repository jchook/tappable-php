<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

use Tap\Smtp\Exception\IOException;
use Tap\Smtp\Exception\TempFail;
use Tap\Smtp\Exception\PermFail;

class Reply
{
	private $code;
	private $lines;

	public function __construct(int $code, array $lines = [])
	{
		$this->code = $code;
		$this->lines = $lines;
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function getLines(): array
	{
		return $this->lines;
	}

	public function isFail(): bool
	{
		return $this->code >= 400;
	}

	public function isPermFail(): bool
	{
		return $this->code >= 500;
	}

	public function isTempFail(): bool
	{
		return $this->code >= 400 && $this->code < 500;
	}

	public function isSuccess(): bool
	{
		return $this->code >= 200 && $this->code < 300;
	}

}
