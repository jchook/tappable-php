<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

use IteratorAggregate;
use Traversable;

class Command implements IteratorAggregate
{
	// [ 'MAIL', 'FROM:<addr@whatever.com>', 'SMTPUTF8', 'BODY=8BITMIME' ]
	//
	// [
	//   'MAIL',
	// 	 new Path('addr', 'whatever.com'),
	// 	 new Param('SMTPUTF8'),
	// 	 new Param('BODY', '8BITMIME'),
	// ]
	//
	private $children = [];

	public function __construct(...$children)
	{
		$this->children = $children;
	}

	public function getIterator(): Traversable
	{
		yield from $this->children;
	}

	public function getChildren(): array
	{
		return $this->children;
	}

	public function getVerb(): string
	{
		return $this->children[0];
	}

	public function getWords(): array
	{
		return array_slice($this->children, 1);
	}
}
