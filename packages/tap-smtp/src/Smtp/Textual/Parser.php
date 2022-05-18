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
use Tap\Smtp\Element\Command\Vrfy;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Origin;
use Tap\Smtp\Element\OriginDomain;
use Tap\Smtp\Element\OriginAddressLiteral;
use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\Path;
use Tap\Smtp\Element\ReversePath;

class Parser
{

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
		case 'RCPT':
		case 'DATA':
		case 'RSET':
		case 'VRFY':
		case 'EXPN':
		case 'HELP':
		case 'NOOP':
		case 'QUIT':
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

	private function parseReversePath(string $path): ReversePath
	{

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
	public function renderParam(Param $param): string
	{
		$name = $param->name;
		$value = $param->value;
		if (is_null($value)) {
			return $name;
		}
		return $name . '=' . $this->stringifyValue($value);
	}

	/**
	 * @param Param[] $params
	 */
	public function renderParams(array $params): string
	{
		return implode(' ', array_map([$this, 'renderParam'], $params));
	}


	/**
	 * Path = "<" [A-d-l ":"] Mailbox ">"
	 */
	public function renderPath(Path $path): string
	{
		if ($path instanceof ReversePath || $path instanceof ForwardPath) {
			if (!$path->mailbox) {
				return '<>';
			}
			return '<' . $this->stringifyLocalPart($path->mailbox->localPart) . '@' .
				$this->stringifyDomain($path->mailbox->domain);
		}
		throw new InvalidArgumentException(
			'Unrecognized Path type: ' . get_class($path)
		);
	}


	/**
	 * domain = dot-atom / domain-literal
	 */
	private function stringifyDomain(string $domain): string
	{
		if ($this->global) {
			return $domain;
		}
		return idn_to_ascii(
			$domain,
			IDNA_DEFAULT,
			INTL_IDNA_VARIANT_UTS46
		);
	}

	/**
	 * RFC 5321 § 4.1.2
	 * Local-part = Dot-string / Quoted-string
	 */
	private function stringifyLocalPart(string $localPart): string
	{
		return Lexeme::isDotString($localPart)
			? $localPart
			: $this->stringifyQuotedString($localPart);
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
	private function stringifyQuotedString(string $str): string
	{
		$str = str_replace('\\', '\\\\', $str);
		$str = str_replace('"', '\\"', $str);
		return '"' . $str . '"';
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
	private function stringifyValue(string $value): string
	{
		// "esmtp-value"
		if (!preg_match('/[\x00-\x20\x7F-\xFF=+ ]/', $value)) {
			return $value;
		}

		// "xtext"
		$final = '';
		$ii = 0;
		do {
			$chr = $value[$ii];
			$ord = ord($chr);
			if ($ord < 0x21 || $ord > 0x7e || $chr === '=' || $chr === '+') {
				$final .= sprintf('+%02X', $ord);
			} else {
				$final .= $chr;
			}
		} while (isset($value[++$ii]));
		return $final;
	}
}

