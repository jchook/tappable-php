<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * 4.1.1.6.  VERIFY (VRFY)
 *
 *    This command asks the receiver to confirm that the argument
 *    identifies a user or mailbox.  If it is a user name, information is
 *    returned as specified in Section 3.5.
 *
 *    This command has no effect on the reverse-path buffer, the forward-
 *    path buffer, or the mail data buffer.
 *
 *    Syntax:
 *
 *       vrfy = "VRFY" SP String CRLF
 */
class Vrfy extends CommandBase
{
  public string $verb = 'VRFY';
	public function __construct(
    public string $string
	)
	{
	}
}

