<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * 4.1.1.7.  EXPAND (EXPN)
 *
 *    This command asks the receiver to confirm that the argument
 *    identifies a mailing list, and if so, to return the membership of
 *    that list.  If the command is successful, a reply is returned
 *    containing information as described in Section 3.5.  This reply will
 *    have multiple lines except in the trivial case of a one-member list.
 *
 *    This command has no effect on the reverse-path buffer, the forward-
 *    path buffer, or the mail data buffer, and it may be issued at any
 *    time.
 *
 *    Syntax:
 *
 *       expn = "EXPN" SP String CRLF
 */
class Expn implements Command
{
  public string $verb = 'EXPN';
	public function __construct(
    public string $string
	)
	{
	}
}


