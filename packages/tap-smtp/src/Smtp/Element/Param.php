<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class Param
{
	public function __construct(
		private string $name,
		private ?string $value = null,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): ?string
	{
		return $this->value;
	}
}

