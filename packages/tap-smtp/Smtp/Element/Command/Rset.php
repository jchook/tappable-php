<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * 4.1.1.5.  RESET (RSET)
 *
 *    This command specifies that the current mail transaction will be
 *    aborted.  Any stored sender, recipients, and mail data MUST be
 *    discarded, and all buffers and state tables cleared.  The receiver
 *    MUST send a "250 OK" reply to a RSET command with no arguments.  A
 *    reset command may be issued by the client at any time.  It is
 *    effectively equivalent to a NOOP (i.e., it has no effect) if issued
 *    immediately after EHLO, before EHLO is issued in the session, after
 *    an end of data indicator has been sent and acknowledged, or
 *    immediately before a QUIT.  An SMTP server MUST NOT close the
 *    connection as the result of receiving a RSET; that action is reserved
 *    for QUIT (see Section 4.1.1.10).
 *
 *    Since EHLO implies some additional processing and response by the
 *    server, RSET will normally be more efficient than reissuing that
 *    command, even though the formal semantics are the same.
 *
 *    There are circumstances, contrary to the intent of this
 *    specification, in which an SMTP server may receive an indication that
 *    the underlying TCP connection has been closed or reset.  To preserve
 *    the robustness of the mail system, SMTP servers SHOULD be prepared
 *    for this condition and SHOULD treat it as if a QUIT had been received
 *    before the connection disappeared.
 *
 *    Syntax:
 *
 *       rset = "RSET" CRLF
 */
class Rset extends CommandBase
{
  public string $verb = 'RSET';
	public function __construct(
	)
	{
	}
}

