<?php declare(strict_types=1);

namespace Tap\Smtp\Textual;

use Tap\Smtp\Element\Command;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Param;

class Renderer
{
	private $global = false;

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
	public function renderCommand(Command $cmd): string
	{
		$verb = $cmd->getVerb();
		$words = $cmd->getWords();

		// Final
		$str = [$verb];

		// Special verbs
		switch (strtoupper($verb)) {
			case 'MAIL':
			case 'RCPT':
				$prefix = $verb === 'MAIL' ? 'FROM:' : 'TO:';
				$str[] = $prefix . $this->renderPath(array_shift($words));
				break;
		}

		// Remainder of words
		foreach ($words as $word) {
			if ($word instanceof Param) {
				$str[] = $this->stringifyParam($word);
			} elseif (is_string($word)) {
				$str[] = $word;
			} else {
				throw new \InvalidArgumentException(
					'Expected Param or string word, but received ' . gettype($word)
				);
			}
		}

		return implode(' ', $str) . "\r\n";
	}

	/**
	 * Path = "<" [A-d-l ":"] Mailbox ">"
	 */
	public function renderPath(?Mailbox $path = null): string
	{
		if (!$path) {
			return '<>';
		}
		return '<' . $this->stringifyLocalPart($path->getLocalPart())
			. '@' . $this->stringifyDomain($path->getDomain()) . '>';
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
	private function stringifyParam(Param $param): string
	{
		$name = $param->getName();
		$value = $param->getValue();
		if (!is_null($value)) {
			$name .= '=' . $this->stringifyValue($value);
		}
		return $name;
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
