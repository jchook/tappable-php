<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * 4.1.1.10.  QUIT (QUIT)
 *
 *    This command specifies that the receiver MUST send a "221 OK" reply,
 *    and then close the transmission channel.
 *
 *    The receiver MUST NOT intentionally close the transmission channel
 *    until it receives and replies to a QUIT command (even if there was an
 *    error).  The sender MUST NOT intentionally close the transmission
 *    channel until it sends a QUIT command, and it SHOULD wait until it
 *    receives the reply (even if there was an error response to a previous
 *    command).  If the connection is closed prematurely due to violations
 *    of the above or system or network failure, the server MUST cancel any
 *    pending transaction, but not undo any previously completed
 *    transaction, and generally MUST act as if the command or transaction
 *    in progress had received a temporary error (i.e., a 4yz response).
 *
 *    The QUIT command may be issued at any time.  Any current uncompleted
 *    mail transaction will be aborted.
 *
 *    Syntax:
 *
 *       quit = "QUIT" CRLF
 */
class Quit extends CommandBase
{
  public string $verb = 'QUIT';
	public function __construct(
	)
	{
	}
}

