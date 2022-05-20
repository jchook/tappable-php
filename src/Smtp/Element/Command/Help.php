<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * 4.1.1.8.  HELP (HELP)
 *
 *    This command causes the server to send helpful information to the
 *    client.  The command MAY take an argument (e.g., any command name)
 *    and return more specific information as a response.
 *
 *    This command has no effect on the reverse-path buffer, the forward-
 *    path buffer, or the mail data buffer, and it may be issued at any
 *    time.
 *
 *    SMTP servers SHOULD support HELP without arguments and MAY support it
 *    with arguments.
 *
 *    Syntax:
 *
 *       help = "HELP" [ SP String ] CRLF
 *
 */
class Help extends CommandBase
{
	public string $verb = 'HELP';
	public function __construct(
		public ?string $string = null,
	)
	{
	}
}

