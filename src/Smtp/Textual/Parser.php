<?php declare(strict_types=1);

namespace Tap\Smtp\Textual;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\EndOfData;
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
use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Origin\AddressLiteral;
use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\EhloKeyword;
use Tap\Smtp\Element\Reply\EhloKeywordBase;
use Tap\Smtp\Element\Reply\EhloParamBase;
use Tap\Smtp\Element\Reply\EhloReply;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Element\Reply\ReplyLine;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Textual\Exception\IncompleteReply;
use Tap\Smtp\Textual\Exception\MultipleReplies;
use Tap\Smtp\Textual\Exception\TextualException;

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
		$string = null;

		// Command only
		switch ($verb) {
		case 'DATA':
		case 'RSET':
		case 'QUIT':
		case '.':
			if (count($words) > 1) {
				throw $this->syntaxError('Unexpected token after command verb ' . $verb);
			}
		}

		// Commands with a single string
		switch ($verb) {
		case 'HELO':
			$origin = new Domain($words[1]);
			return new Helo($origin);

		case 'EHLO':
			$origin = $this->parseOrigin($words[1]);
			return new Ehlo($origin);

		case 'MAIL':
			[,$pathStr] = $this->expectRegex('/^FROM:(<[^>]*>)$/i', $words[1]);
			$path = $this->parseReversePath($pathStr);
			$params = $this->parseParams(array_slice($words, 2));
			return new MailFrom($path, $params);

		case 'RCPT':
			[,$pathStr] = $this->expectRegex('/^TO:(<[^>]+>)$/i', $words[1]);
			$path = $this->parseForwardPath($pathStr);
			$params = $this->parseParams(array_slice($words, 2));
			return new RcptTo($path, ...$params);

		case 'DATA': return new Data();
		case 'RSET': return new Rset();
		case 'QUIT': return new Quit();
		case '.': return new EndOfData();
		}

		// The rest of these commands accept a single string argument
		$string = count($words) > 1 ? implode(' ', array_slice($words, 1)) : null;

		switch ($verb) {
		case 'EXPN': return new Expn($string ?? '');
		case 'HELP': return new Help($string);
		case 'NOOP': return new Noop($string);
		case 'VRFY': return new Vrfy($string ?? '');
		default: return new Unknown($verb, $string);
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
		if ($result === false || $result === 0) {
			throw new TextualException(
				'Syntax error: ' . ($errorMsg ?? 'unexpected characters encountered')
			);
		}
		return $matches;
	}

	// Thinking I will need to use this
	// protected function expectCrlf(string $subject): void
	// {
	// 	// TODO: parser could be set-up more like MIME and give more helpful
	// 	// error messages.
	// 	if (substr($subject, -2) !== "\r\n") {
	// 		throw new TextualException(
	// 			'Missing CRLF'
	// 		);
	// 	}
	// }

	/**
	 * Returns true if the reply line is not the final line of the reply.
	 */
	public static function isPartialReply(string $replyLine): bool
	{
		return ($replyLine[3] ?? null) === '-';
	}

	private function parseOrigin(string $origin): Origin
	{
		if (substr($origin, 0, 1) === '[') {
			return new AddressLiteral(trim($origin, '[]'));
		}
		return $this->parseDomain($origin);
	}

	/**
	 *   Domain         = sub-domain *("." sub-domain)
	 *
	 *   sub-domain     = Let-dig [Ldh-str]
	 *
	 *   Let-dig        = ALPHA / DIGIT
	 *
	 *   Ldh-str        = *( ALPHA / DIGIT / "-" ) Let-dig
	 *
	 */
	private function parseDomain(string $domain): Domain
	{
		$letDig = '[A-Z0-9\x80-\xFF]';
		$letDigHyphen = '[A-Z0-9\x80-\xFF-]';
		$subDomain ="$letDig(($letDigHyphen)*$letDig)?";
		$this->expectRegex(
			"/^$subDomain(\.$subDomain)*$/i",
			$domain,
			'Invalid domain: ' . $domain
		);
		return new Domain($domain);
	}

	public function parseGreetingOrReply(string $lines)
	{
		$lines = $this->parseReplyLines($lines);
		try {
			return $this->parseGreetingFromLines($lines);
		} catch (TextualException) {
			return $this->parseReplyFromLines($lines);
		}
	}

	public function parseGreeting(string $lines): Greeting
	{
		return $this->parseGreetingFromLines($this->parseReplyLines($lines));
	}

	/**
	 *  Greeting       = ( "220 " (Domain / address-literal)
 	 *                 [ SP textstring ] CRLF ) /
 	 *                 ( "220-" (Domain / address-literal)
 	 *                 [ SP textstring ] CRLF
 	 *                 *( "220-" [ textstring ] CRLF )
 	 *                 "220" [ SP textstring ] CRLF )
	 *
	 * @param ReplyLine[] $replyLines
	 */
	protected function parseGreetingFromLines(array $replyLines): Greeting
	{
		$this->validateOneReply($replyLines);
		$last = $replyLines[count($replyLines) - 1];
		$code = $last->code;
		if ($code->value !== '220') {
			throw new TextualException('Unexpected greeting code: ' . $code->value);
		}
		$first = array_shift($replyLines);
		$parts = explode(' ', $first->message, 2);
		$origin = $this->parseOrigin($parts[0]);
		$messages = [];
		if (isset($parts[1])) {
			$messages[] = $parts[1];
		}
		foreach ($replyLines as $replyLine) {
			$messages[] = $replyLine->message;
		}
		return new Greeting($origin, $messages);
	}

	/**
	 * RFC 5321 § 4.2
	 *
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
			$this->expectRegex(
				'/^([2-5][0-5][0-9])( |-)?/',
				$replyStr,
				'Expected Reply-line',
			);

		// Parse the code
		$code = new Code($codeStr);

		// Snag the full line
		$message = substr($replyStr, strlen($prefix));

		// Continue reading lines into this reply?
		$continue = $continueStr === '-' ? true : false;

		//
		return new ReplyLine($code, $message, $continue);
	}

	/**
	 * @return ReplyLine[]
	 */
	public function parseReplyLines(string $lines): array
	{
		$lines = explode("\r\n", $lines);
		$last = array_pop($lines);
		if (!empty($last)) {
			throw new TextualException('Received non-terminated reply line');
		}
		return array_map([$this, 'parseReplyLine'], $lines);
	}

	/**
	 * This is a line-based parser (for the most part). To support PIPELINING and
	 * multiline replies, this function will parse a group of replies all together
	 * IFF they are all complete (non-partial) and syntactically correct.
	 *
	 * Otherwise an exception is thrown.
	 *
	 * @param ReplyLine[] $replyLines
	 * @return ReplyLine[][]
	 */
	protected function groupReplyLines(array $replyLines): array
	{
		$replyGroups = [];
		$replyGroup = [];
		foreach ($replyLines as $line) {
			$replyGroup[] = $line;
			if ($line->continue) {
				continue;
			}
			$replyGroups[] = $replyGroup;
			$replyGroup = [];
		}
		if ($replyGroup) {
			throw new IncompleteReply(
				'Parsed ' . count($replyGroups) . ' replies, but the last one was' .
				'incomplete.'
			);
		}
		return $replyGroups;
	}

	/**
	 * @return Reply[]
	 */
	public function parseReplies(string $lines): array
	{
		$replyLineGroups = $this->groupReplyLines($this->parseReplyLines($lines));
		return array_map([$this, 'parseReplyFromLines'], $replyLineGroups);
	}

	public function parseReply(string $lines): Reply
	{
		return $this->parseReplyFromLines($this->parseReplyLines($lines));
	}

	/**
	 *   In particular, the 220, 221, 251, 421, and 551 reply codes
	 *   are associated with message text that must be parsed and interpreted
	 *   by machines.
	 *
	 * @param ReplyLine[] $replyLines
	 */
	protected function parseReplyFromLines(array $replyLines): Reply
	{
		$this->validateOneReply($replyLines);

		// Use the last line's code (RFC 821)
		$last = $replyLines[count($replyLines) - 1];
		$code = $last->code;

		// Generic
		$messages = array_map(fn(ReplyLine $x) => $x->message, $replyLines);
		return new GenericReply($code, $messages);
	}

	/**
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
	 * @param ReplyLine[] $replyLines
	 */
	public function parseEhloReply(string $lines): EhloReply
	{
		return $this->parseEhloReplyFromLines($this->parseReplyLines($lines));
	}

	protected function parseEhloReplyFromLines(array $replyLines): EhloReply
	{
		$this->validateOneReply($replyLines);
		$last = $replyLines[count($replyLines) - 1];
		$code = $last->code;
		$first = array_shift($replyLines);
		$parts = explode(' ', $first->message, 2);
		$domain = $this->parseDomain($parts[0]);
		$greet = $parts[1] ?? null;
		$keywords = [];
		foreach ($replyLines as $replyLine) {
			$keywords[] = $this->parseEhloKeyword($replyLine->message);
		}
		return new EhloReply($code, $domain, $greet, $keywords);
	}

	/**
	 *   ehlo-keyword   = (ALPHA / DIGIT) *(ALPHA / DIGIT / "-")
	 *                    ; additional syntax of ehlo-params depends on
	 *                    ; ehlo-keyword
	 *
	 *   ehlo-param     = 1*(%d33-126)
	 *                    ; any CHAR excluding <SP> and all
	 *                    ; control characters (US-ASCII 0-31 and 127
	 *                    ; inclusive)
	 *
	 * @param ReplyLine[] $replyLines
	 */
	protected function parseEhloKeyword(string $line): EhloKeyword
	{
		$parts = explode(' ', $line);
		$name = array_shift($parts);
		$params = array_map(fn($x) => new EhloParamBase($x), $parts);
		return new EhloKeywordBase($name, $params);
	}

	/**
	 * Validate that the array of reply lines contains only one reply.
	 * @param ReplyLine[] $replyLines
	 */
	private function validateOneReply(array $replyLines): void
	{
		$last = array_pop($replyLines);
		if (!$last || $last->continue) {
			throw new IncompleteReply('Incomplete reply');
		}
		foreach ($replyLines as $replyLine) {
			if (!$replyLine->continue) {
				throw new MultipleReplies('Expected one reply but found multiple');
			}
		}
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
	public function parseReversePath(string $path): ReversePath
	{
		// Null sender?
		if ($path === '<>') {
			return new ReversePath(null);
		}
		$mailboxStr = $this->extractMailboxFromPathString($path);
		$mailbox = $this->parseMailbox($mailboxStr);
		return new ReversePath($mailbox);
	}

	public function parseForwardPath(string $path): ForwardPath
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

	protected function syntaxError(string $message): TextualException
	{
		// TODO beef this up
		return new TextualException('Syntax error: ' . $message);
	}

	public function parseMailbox(string $mailboxStr): Mailbox
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
		// We must pass `true` to isDotString() here because RFC 6530 et al do
		// not provide an ASCII alternative / encoding for <Local-part>.
		//
		// It's not easy to fully grok from the specs, but the Postfix SMTPUTF8
		// docs explicitly mention this specific problem:
		//
		// > Some background: According to RFC 6530 and related documents, an
		// > internationalized domain name can appear in two forms: the UTF-8 form,
		// > and the ASCII (xn--mumble) form. An internationalized address localpart
		// > must be encoded in UTF-8; the RFCs do not define an ASCII alternative
		// > form.
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
			throw $this->syntaxError('Invalid quoted string, missing open quote');
		}
		if (substr($str, -1, 1) !== '"') {
			throw $this->syntaxError('Invalid quoted string, missing close quote');
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
		return preg_replace_callback('/\+([0-9A-F]{2})/i', function ($matches) {
			$chr = chr(hexdec($matches[1]));
			return $chr;
		}, $str);
	}
}

