<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

/**
 * Write custom param classes as needed to handle special internal syntax, etc
 */
class EhloParamBase implements EhloParam
{
	public function __construct(
		public string $string,
	)
	{
	}

	public function __toString()
	{
		return $this->string;
	}
}


