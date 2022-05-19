<?php declare(strict_types=1);

namespace Tap\Smtp\Support;

/**
 * In PHP, you cannot properly distinguish arrays or strings from callable.
 *
 * e.g. Imagine if I have a function called 'alpha', and a string 'alpha',
 * completely unrelated to the function. It will pass is_callable(). :(
 *
 * Therefore, we need this class.
 */
class Callback
{
	private $fn;
	private $args = [];

	public function __construct(callable $fn, array $args = [])
	{
		$this->fn = $fn;
		$this->args = $args;
	}

	/**
	 * @throws \Throwable
	 */
	public function __invoke(...$args)
	{
		return ($this->fn)(...$args);
	}

	public function bind(...$args): self
	{
		$clone = clone $this;
		$clone->args = $args;
		return $clone;
	}
}
