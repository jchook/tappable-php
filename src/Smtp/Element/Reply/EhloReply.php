<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

use Tap\Smtp\Element\Origin\Domain;

/**
 * RFC 5321 § 4.1.1.1.  Extended HELLO (EHLO) or HELLO (HELO)
 *
 *   Normally, the response to EHLO will be a multiline reply.  Each line
 *   of the response contains a keyword and, optionally, one or more
 *   parameters.  Following the normal syntax for multiline replies, these
 *   keywords follow the code (250) and a hyphen for all but the last
 *   line, and the code and a space for the last line.  The syntax for a
 *   positive response, using the ABNF notation and terminal symbols of
 *   RFC 5234 [7], is:
 *
 *   ehlo-ok-rsp    = ( "250" SP Domain [ SP ehlo-greet ] CRLF )
 *                    / ( "250-" Domain [ SP ehlo-greet ] CRLF
 *                    *( "250-" ehlo-line CRLF )
 *                    "250" SP ehlo-line CRLF )
 *
 *   ehlo-greet     = 1*(%d0-9 / %d11-12 / %d14-127)
 *                    ; string of any characters other than CR or LF
 *
 *   ehlo-line      = ehlo-keyword *( SP ehlo-param )
 *
 *   ehlo-keyword   = (ALPHA / DIGIT) *(ALPHA / DIGIT / "-")
 *                    ; additional syntax of ehlo-params depends on
 *                    ; ehlo-keyword
 *
 *   ehlo-param     = 1*(%d33-126)
 *                    ; any CHAR excluding <SP> and all
 *                    ; control characters (US-ASCII 0-31 and 127
 *                    ; inclusive)
 *
 *   Although EHLO keywords may be specified in upper, lower, or mixed
 *   case, they MUST always be recognized and processed in a case-
 *   insensitive manner.  This is simply an extension of practices
 *   specified in RFC 821 and Section 2.4.
 *
 *   The EHLO response MUST contain keywords (and associated parameters if
 *   required) for all commands not listed as "required" in Section 4.5.1
 *   excepting only private-use commands as described in Section 4.1.5.
 *   Private-use commands MAY be listed.
 */
interface EhloReply extends Reply
{
	public function getDomain(): Domain;
	public function getMessage(): string;

	/**
	 * @return EhloKeyword[]
	 */
	public function getKeywords(): array;
}

