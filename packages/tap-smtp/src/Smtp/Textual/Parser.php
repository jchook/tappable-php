<?php declare(strict_types=1);

namespace Tap\Smtp\Textual;

use InvalidArgumentException;
use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\Expn;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\Help;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Noop;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Command\Rset;
use Tap\Smtp\Element\Command\Unknown;
use Tap\Smtp\Element\Command\Vrfy;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Origin;
use Tap\Smtp\Element\OriginDomain;
use Tap\Smtp\Element\OriginAddressLiteral;
use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Element\Reply\ReplyLine;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Exception\TimeoutException;

class Parser
{
	public function __construct(
		public bool $smtputf8 = true
	)
	{
	}

	/**
	 * ehlo = "EHLO" SP ( Domain / address-literal ) CRLF
	 * helo = "HELO" SP Domain CRLF
	 *
	 * mail = "MAIL FROM:" Reverse-path [SP Mail-parameters] CRLF
	 *
	 * rcpt = "RCPT TO:" ( "<Postmaster@" Domain ">" / "<Postmaster>" /
	 *   Forward-path ) [SP Rcpt-parameters] CRLF
	 *
	 * data = "DATA" CRLF
	 * rset = "RSET" CRLF
	 * vrfy = "VRFY" SP String CRLF
	 * expn = "EXPN" SP String CRLF
	 * help = "HELP" [ SP String ] CRLF
	 * noop = "NOOP" [ SP String ] CRLF
	 * quit = "QUIT" CRLF
	 */
	public function parseCommand(string $line): Command
	{
		$words = explode(' ', trim($line));
		$verb = strtoupper($words[0]);
		switch ($verb) {

		case 'HELO':
			$origin = new OriginDomain($words[1]);
			return new Helo($origin);

		case 'EHLO':
			$origin = $this->parseOrigin($words[1]);
			return new Ehlo($origin);

		case 'MAIL':
			[,$pathStr] = $this->expectRegex('/^FROM:(<[^>]*>)$/i', $words[1]);
			$path = $this->parseReversePath($pathStr);
			$params = $this->parseParams(array_slice($words, 2));
			return new MailFrom($path, ...$params);

		case 'RCPT':
			[,$pathStr] = $this->expectRegex('/^TO:(<[^>]+>)$/i', $words[1]);
			$path = $this->parseForwardPath($pathStr);
			$params = $this->parseParams(array_slice($words, 2));
			return new RcptTo($path, ...$params);

		case 'DATA':
		case 'RSET':
		case 'QUIT':
			if (count($words) > 1) {
				throw $this->syntaxError('Unexpected token after ' . $verb);
			}
			return $this->parseCommandSimple($verb);

		case 'EXPN':
		case 'HELP':
		case 'NOOP':
		case 'VRFY':
			$string = implode(' ', array_slice($words, 1));
			return $this->parseCommandWithOneString($verb, $string);

		default:
			$string = count($words) > 1 ? implode(' ', array_slice($words, 1)) : null;
			return new Unknown($verb, $string);
		}
	}

	protected function parseCommandWithOneString(string $verb, string $string): Command
	{
		switch ($verb) {
		case 'EXPN': return new Expn($string);
		case 'HELP': return new Help($string);
		case 'NOOP': return new Noop($string);
		case 'VRFY': return new Vrfy($string);
		default: throw $this->syntaxError('Unexpected command with string: ' . $verb);
		}
	}

	protected function parseCommandSimple(string $verb): Command
	{
		switch ($verb) {
		case 'DATA': return new Data();
		case 'RSET': return new Rset();
		case 'QUIT': return new Quit();
		default: throw $this->syntaxError('Unexpected simple command: ' . $verb);
		}
	}

	protected function expectRegex(
		string $pattern,
		string $subject,
		?string $errorMsg = null
	): array
	{
		$matches = [];
		$result = preg_match($pattern, $subject, $matches);
		if ($result === false) {
			throw new InvalidArgumentException(
				'Syntax error: ' . ($errorMsg ?? 'unexpected characters encountered')
			);
		}
		return $matches;
	}

	private function parseOrigin(string $origin): Origin
	{
		if (substr($origin, 0, 1) === '[') {
			return new OriginAddressLiteral(trim($origin, '[]'));
		}
		return new OriginDomain($origin);
	}

	/**
	 * RFC 5321 § 4.2
	 *
	 *  Greeting       = ( "220 " (Domain / address-literal)
 	 *                 [ SP textstring ] CRLF ) /
 	 *                 ( "220-" (Domain / address-literal)
 	 *                 [ SP textstring ] CRLF
 	 *                 *( "220-" [ textstring ] CRLF )
 	 *                 "220" [ SP textstring ] CRLF )
 	 *  textstring     = 1*(%d09 / %d32-126) ; HT, SP, Printable US-ASCII
 	 *  Reply-line     = *( Reply-code "-" [ textstring ] CRLF )
 	 *                 Reply-code [ SP textstring ] CRLF
	 *  Reply-code     = %x32-35 %x30-35 %x30-39
	 *
	 *  Since, in violation of this
	 *  specification, the text is sometimes not sent, clients that do not
	 *  receive it SHOULD be prepared to process the code alone
	 *
	 * How do we handle this? What about partial replies?
	 */
	public function parseReplyLine(string $replyStr): ReplyLine
	{
		// Grep the reply code and conintuation indicator
		[$prefix, $codeStr, $continueStr] =
			$this->expectRegex('/^([2-5][0-9][0-9])( |-)?/', $replyStr);

		// Parse the code
		$code = new Code((int)$codeStr);

		// Snag the full line
		$message = substr($replyStr, strlen($prefix));

		// Continue reading lines into this reply?
		$continue = $continueStr === '-' ? true : false;

		//
		return new ReplyLine($code, $message, $continue);
	}

	/**
	 *   Reverse-path   = Path / "<>"
	 *
	 *   Forward-path   = Path
	 *
	 *   Path           = "<" [ A-d-l ":" ] Mailbox ">"
	 *
	 *   A-d-l          = At-domain *( "," At-domain )
	 *                  ; Note that this form, the so-called "source
	 *                  ; route", MUST BE accepted, SHOULD NOT be
	 *                  ; generated, and SHOULD be ignored.
	 *
	 *   At-domain      = "@" Domain
	 *
	 *   Domain         = sub-domain *("." sub-domain)
	 *
	 *   sub-domain     = Let-dig [Ldh-str]
	 *
	 *   Let-dig        = ALPHA / DIGIT
	 *
	 *   Ldh-str        = *( ALPHA / DIGIT / "-" ) Let-dig
	 *
	 *   address-literal  = "[" ( IPv4-address-literal /
	 *                    IPv6-address-literal /
	 *                    General-address-literal ) "]"
	 *                    ; See Section 4.1.3
	 *
	 *   Mailbox        = Local-part "@" ( Domain / address-literal )
	 *
	 *   Local-part     = Dot-string / Quoted-string
	 *                  ; MAY be case-sensitive
	 *
	 *
	 *   Dot-string     = Atom *("."  Atom)
	 *
	 *   Atom           = 1*atext
	 *
	 *   Quoted-string  = DQUOTE *QcontentSMTP DQUOTE
	 *
	 *   QcontentSMTP   = qtextSMTP / quoted-pairSMTP
	 *
	 *   quoted-pairSMTP  = %d92 %d32-126
	 *                    ; i.e., backslash followed by any ASCII
	 *                    ; graphic (including itself) or SPace
	 *
	 *   qtextSMTP      = %d32-33 / %d35-91 / %d93-126
	 *                  ; i.e., within a quoted string, any
	 *                  ; ASCII graphic or space is permitted
	 *                  ; without blackslash-quoting except
	 *                  ; double-quote and the backslash itself.
	 *
	 *   String         = Atom / Quoted-string
	 */
	private function parseReversePath(string $path): ReversePath
	{
		// Null sender?
		if ($path === '<>') {
			return new ReversePath(null);
		}
		$mailboxStr = $this->extractMailboxFromPathString($path);
		$mailbox = $this->parseMailbox($mailboxStr);
		return new ReversePath($mailbox);
	}

	private function parseForwardPath(string $path): ForwardPath
	{
		$mailboxStr = $this->extractMailboxFromPathString($path);
		$mailbox = $this->parseMailbox($mailboxStr);
		return new ForwardPath($mailbox);
	}

	private function extractMailboxFromPathString(string $path): string
	{
		[, $mailboxStr] = $this->expectRegex(
			'/^<' .
				// Non-capturing group for A-d-l
			  // According to the spec we SHOULD ignore this
				'(?:@[^:]+:)?' .

				// The actual mailbox string here, which we'll parse separately.
				'([^>]+)' .
			'>$/',
			$path
		);
		return $mailboxStr;
	}

	protected function syntaxError(string $message): \Throwable
	{
		// TODO beef this up
		return new InvalidArgumentException('Syntax error: ' . $message);
	}

	private function parseMailbox(string $mailboxStr): Mailbox
	{
		// Find the position of the last @
		$atPos = strrpos($mailboxStr, '@');
		if ($atPos === false) {
			throw $this->syntaxError('Invalid mailbox: ' . $mailboxStr);
		}

		// Segment out the origin
		$origin = $this->parseOrigin(substr($mailboxStr, $atPos + 1));
		$localPart = $this->parseLocalPart(substr($mailboxStr, 0, $atPos));

		// Yay 📬
		return new Mailbox($localPart, $origin);
	}

	/**
	 * RFC 5321 § 4.1.2
	 *
   * Mail-parameters  = esmtp-param *(SP esmtp-param)
   * Rcpt-parameters  = esmtp-param *(SP esmtp-param)
   * esmtp-param    = esmtp-keyword ["=" esmtp-value]
   * esmtp-keyword  = (ALPHA / DIGIT) *(ALPHA / DIGIT / "-")
   * esmtp-value    = 1*(%d33-60 / %d62-126)
   *                ; any CHAR excluding "=", SP, and control
   *                ; characters.  If this string is an email address,
   *                ; i.e., a Mailbox, then the "xtext" syntax [32]
   *                ; SHOULD be used.
	 */
	public function parseParam(string $param): Param
	{
		// Does the param have a value?
		$eqPos = strpos($param, '=');
		if ($eqPos) {
			[$name, $value] = explode('=', $param, 2);
			$value = $this->parseXtext($value);
			return new Param($name, $value);
		}
		return new Param($param);
	}

	public function parseParams(array $params): array
	{
		return array_map([$this, 'parseParam'], $params);
	}

	/**
	 * RFC 5321 § 4.1.2
	 * Local-part = Dot-string / Quoted-string
	 */
	private function parseLocalPart(string $localPart): string
	{
		return Lexeme::isDotString($localPart, true)
			? $localPart
			: $this->parseQuotedString($localPart);
	}

	/**
	 * RFC 5321 § 4.1.2
	 *
	 * Quoted-string = DQUOTE *QcontentSMTP DQUOTE
   *
   * QcontentSMTP = qtextSMTP / quoted-pairSMTP
   *
   * quoted-pairSMTP = %d92 %d32-126
   *  ; i.e., backslash followed by any ASCII
   *  ; graphic (including itself) or SPace
   *
   * qtextSMTP = %d32-33 / %d35-91 / %d93-126
   *  ; i.e., within a quoted string, any
   *  ; ASCII graphic or space is permitted
   *  ; without blackslash-quoting except
   *  ; double-quote and the backslash itself.
	 */
	private function parseQuotedString(string $str): string
	{
		if (substr($str, 0, 1) !== '"') {
			$this->syntaxError('Invalid quoted string, missing open quote');
		}
		if (substr($str, -1, 1) !== '"') {
			$this->syntaxError('Invalid quoted string, missing close quote');
		}
		$str = substr($str, 1, -1);
		// TODO: Not sure if this actually works in all cases. I think it does?
		$str = str_replace('\\\\', '\\', $str);
		$str = str_replace('\\"', '"', $str);
		return $str;
	}

	/**
	 * RFC 5321 § 4.2.1 "esmtp-value"
	 * RFC 3461 § 4 "xtext"
	 *
	 * xtext = *( xchar / hexchar )
	 * xchar = any ASCII CHAR between "!" (33) and "~" (126) inclusive,
	 *   except for "+" and "=".
	 * hexchar = ASCII "+" immediately followed by two upper case hexadecimal
	 *   digits
	 */
	protected function parseXtext(string $str): string
	{
		return preg_replace_callback('/\+([0-9A-F]){2}/i', function ($matches) {
			return chr(hexdec($matches[1]));
		}, $str);
	}
}

