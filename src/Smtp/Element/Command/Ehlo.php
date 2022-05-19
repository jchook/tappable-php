<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

use Tap\Smtp\Element\Origin;

/**
 * 4.1.1.1.  Extended HELLO (EHLO) or HELLO (HELO)
 *
 *    These commands are used to identify the SMTP client to the SMTP
 *    server.  The argument clause contains the fully-qualified domain name
 *    of the SMTP client, if one is available.  In situations in which the
 *    SMTP client system does not have a meaningful domain name (e.g., when
 *    its address is dynamically allocated and no reverse mapping record is
 *
 *
 *    available), the client SHOULD send an address literal (see
 *    Section 4.1.3).
 *
 *    RFC 2821, and some earlier informal practices, encouraged following
 *    the literal by information that would help to identify the client
 *    system.  That convention was not widely supported, and many SMTP
 *    servers considered it an error.  In the interest of interoperability,
 *    it is probably wise for servers to be prepared for this string to
 *    occur, but SMTP clients SHOULD NOT send it.
 *
 *    The SMTP server identifies itself to the SMTP client in the
 *    connection greeting reply and in the response to this command.
 *
 *    A client SMTP SHOULD start an SMTP session by issuing the EHLO
 *    command.  If the SMTP server supports the SMTP service extensions, it
 *    will give a successful response, a failure response, or an error
 *    response.  If the SMTP server, in violation of this specification,
 *    does not support any SMTP service extensions, it will generate an
 *    error response.  Older client SMTP systems MAY, as discussed above,
 *    use HELO (as specified in RFC 821) instead of EHLO, and servers MUST
 *    support the HELO command and reply properly to it.  In any event, a
 *    client MUST issue HELO or EHLO before starting a mail transaction.
 *
 *    These commands, and a "250 OK" reply to one of them, confirm that
 *    both the SMTP client and the SMTP server are in the initial state,
 *    that is, there is no transaction in progress and all state tables and
 *    buffers are cleared.
 *
 *    Syntax:
 *
 *    ehlo           = "EHLO" SP ( Domain / address-literal ) CRLF
 *
 *    helo           = "HELO" SP Domain CRLF
 *
 *    Normally, the response to EHLO will be a multiline reply.  Each line
 *    of the response contains a keyword and, optionally, one or more
 *    parameters.  Following the normal syntax for multiline replies, these
 *    keywords follow the code (250) and a hyphen for all but the last
 *    line, and the code and a space for the last line.  The syntax for a
 *    positive response, using the ABNF notation and terminal symbols of
 *    RFC 5234 [7], is:
 *
 *    ehlo-ok-rsp    = ( "250" SP Domain [ SP ehlo-greet ] CRLF )
 *                     / ( "250-" Domain [ SP ehlo-greet ] CRLF
 *                     *( "250-" ehlo-line CRLF )
 *                     "250" SP ehlo-line CRLF )
 *
 *    ehlo-greet     = 1*(%d0-9 / %d11-12 / %d14-127)
 *                     ; string of any characters other than CR or LF
 *
 *    ehlo-line      = ehlo-keyword *( SP ehlo-param )
 *
 *    ehlo-keyword   = (ALPHA / DIGIT) *(ALPHA / DIGIT / "-")
 *                     ; additional syntax of ehlo-params depends on
 *                     ; ehlo-keyword
 *
 *    ehlo-param     = 1*(%d33-126)
 *                     ; any CHAR excluding <SP> and all
 *                     ; control characters (US-ASCII 0-31 and 127
 *                     ; inclusive)
 *
 *    Although EHLO keywords may be specified in upper, lower, or mixed
 *    case, they MUST always be recognized and processed in a case-
 *    insensitive manner.  This is simply an extension of practices
 *    specified in RFC 821 and Section 2.4.
 *
 *    The EHLO response MUST contain keywords (and associated parameters if
 *    required) for all commands not listed as "required" in Section 4.5.1
 *    excepting only private-use commands as described in Section 4.1.5.
 *    Private-use commands MAY be listed.
 */
class Ehlo extends CommandBase
{
  public string $verb = 'EHLO';
	public function __construct(
    public Origin $origin
	)
	{
	}
}

