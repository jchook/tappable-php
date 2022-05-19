<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\ForwardPath;

/**
 * 4.1.1.3.  RECIPIENT (RCPT)
 *
 *    This command is used to identify an individual recipient of the mail
 *    data; multiple recipients are specified by multiple uses of this
 *    command.  The argument clause contains a forward-path and may contain
 *    optional parameters.
 *
 *    The forward-path normally consists of the required destination
 *    mailbox.  Sending systems SHOULD NOT generate the optional list of
 *    hosts known as a source route.  Receiving systems MUST recognize
 *    source route syntax but SHOULD strip off the source route
 *    specification and utilize the domain name associated with the mailbox
 *    as if the source route had not been provided.
 *
 *    Similarly, relay hosts SHOULD strip or ignore source routes, and
 *    names MUST NOT be copied into the reverse-path.  When mail reaches
 *    its ultimate destination (the forward-path contains only a
 *    destination mailbox), the SMTP server inserts it into the destination
 *    mailbox in accordance with its host mail conventions.
 *
 *    This command appends its forward-path argument to the forward-path
 *    buffer; it does not change the reverse-path buffer nor the mail data
 *    buffer.
 *
 *    For example, mail received at relay host xyz.com with envelope
 *    commands
 *
 *       MAIL FROM:<userx@y.foo.org>
 *       RCPT TO:<@hosta.int,@jkl.org:userc@d.bar.org>
 *
 *    will normally be sent directly on to host d.bar.org with envelope
 *    commands
 *
 *       MAIL FROM:<userx@y.foo.org>
 *       RCPT TO:<userc@d.bar.org>
 *
 *    As provided in Appendix C, xyz.com MAY also choose to relay the
 *    message to hosta.int, using the envelope commands
 *
 *       MAIL FROM:<userx@y.foo.org>
 *       RCPT TO:<@hosta.int,@jkl.org:userc@d.bar.org>
 *
 *    or to jkl.org, using the envelope commands
 *
 *       MAIL FROM:<userx@y.foo.org>
 *       RCPT TO:<@jkl.org:userc@d.bar.org>
 *
 *    Attempting to use relaying this way is now strongly discouraged.
 *    Since hosts are not required to relay mail at all, xyz.com MAY also
 *    reject the message entirely when the RCPT command is received, using
 *    a 550 code (since this is a "policy reason").
 *
 *    If service extensions were negotiated, the RCPT command may also
 *    carry parameters associated with a particular service extension
 *    offered by the server.  The client MUST NOT transmit parameters other
 *    than those associated with a service extension offered by the server
 *    in its EHLO response.
 *
 *    Syntax:
 *
 *       rcpt = "RCPT TO:" ( "<Postmaster@" Domain ">" / "<Postmaster>" /
 *                   Forward-path ) [SP Rcpt-parameters] CRLF
 *
 *                   Note that, in a departure from the usual rules for
 *                   local-parts, the "Postmaster" string shown above is
 *                   treated as case-insensitive.
 */
class RcptTo implements Command
{
  public string $verb = 'RCPT';
  /**
   * @var Param[]
   */
  public array $params = [];
	public function __construct(
    public ForwardPath $forwardPath,
    ...$params,
	)
	{
    $this->params = $params;
	}
}

