<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * Unknown command!
 *
 * Middleware can parse it for us.
 */
class Unknown implements Command
{
	public function __construct(
    public string $verb,
    public ?string $string = null
	)
	{
	}
}


