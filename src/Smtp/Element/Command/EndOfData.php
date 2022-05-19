<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

/**
 * 4.1.1.4.  DATA (DATA)
 *
 *    The mail data are terminated by a line containing only a period, that
 *    is, the character sequence "<CRLF>.<CRLF>", where the first <CRLF> is
 *    actually the terminator of the previous line (see Section 4.5.2).
 *    This is the end of mail data indication.  The first <CRLF> of this
 *    terminating sequence is also the <CRLF> that ends the final line of
 *    the data (message text) or, if there was no mail data, ends the DATA
 *    command itself (the "no mail data" case does not conform to this
 *    specification since it would require that neither the trace header
 *    fields required by this specification nor the message header section
 *    required by RFC 5322 [4] be transmitted).  An extra <CRLF> MUST NOT
 *    be added, as that would cause an empty line to be added to the
 *    message.  The only exception to this rule would arise if the message
 *    body were passed to the originating SMTP-sender with a final "line"
 *    that did not end in <CRLF>; in that case, the originating SMTP system
 *    MUST either reject the message as invalid or add <CRLF> in order to
 *    have the receiving SMTP server recognize the "end of data" condition.
 *
 *    The custom of accepting lines ending only in <LF>, as a concession to
 *    non-conforming behavior on the part of some UNIX systems, has proven
 *    to cause more interoperability problems than it solves, and SMTP
 *    server systems MUST NOT do this, even in the name of improved
 *    robustness.  In particular, the sequence "<LF>.<LF>" (bare line
 *    feeds, without carriage returns) MUST NOT be treated as equivalent to
 *    <CRLF>.<CRLF> as the end of mail data indication.
 *
 *    Receipt of the end of mail data indication requires the server to
 *    process the stored mail transaction information.  This processing
 *    consumes the information in the reverse-path buffer, the forward-path
 *    buffer, and the mail data buffer, and on the completion of this
 *    command these buffers are cleared.  If the processing is successful,
 *    the receiver MUST send an OK reply.  If the processing fails, the
 *    receiver MUST send a failure reply.  The SMTP model does not allow
 *    for partial failures at this point: either the message is accepted by
 *    the server for delivery and a positive response is returned or it is
 *    not accepted and a failure reply is returned.  In sending a positive
 *    "250 OK" completion reply to the end of data indication, the receiver
 *    takes full responsibility for the message (see Section 6.1).  Errors
 *    that are diagnosed subsequently MUST be reported in a mail message,
 *    as discussed in Section 4.4.
 *
 */
class EndOfData extends CommandBase
{
  public string $verb = '.';
	public function __construct(
	)
	{
	}
}


