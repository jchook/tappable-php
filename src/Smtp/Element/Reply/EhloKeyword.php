<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

/**
 */
interface EhloKeyword
{
	public function getName(): string;

	/**
	 * @return EhloParam[]
	 */
	public function getParams(): array;
}



