<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * 4.1.1.9.  NOOP (NOOP)
 *
 *    This command does not affect any parameters or previously entered
 *    commands.  It specifies no action other than that the receiver send a
 *    "250 OK" reply.
 *
 *    This command has no effect on the reverse-path buffer, the forward-
 *    path buffer, or the mail data buffer, and it may be issued at any
 *    time.  If a parameter string is specified, servers SHOULD ignore it.
 *
 *    Syntax:
 *
 *       noop = "NOOP" [ SP String ] CRLF
 */
class Noop implements Command
{
  public string $verb = 'NOOP';
	public function __construct(
    public ?string $string = null
	)
	{
	}
}

