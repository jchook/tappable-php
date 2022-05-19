<?php

namespace Tap\Smtp\Support;

class Container
{
	private $state = [];
	public function __get(string $attr)
	{
		return $this->state[$attr] ?? null;
	}

  public function __set(string $attr, $val)
  {
    $this->state[$attr] = $val;
  }

  public function __isset(string $attr): bool
  {
    return isset($this->state[$attr]);
  }

  public function __unset(string $attr): void
  {
    unset($this->state[$attr]);
  }
}

