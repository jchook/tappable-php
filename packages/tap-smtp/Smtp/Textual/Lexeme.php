<?php declare(strict_types=1);

namespace Tap\Smtp\Textual;

class Lexeme
{

	/**
	 * RFC 5321 § 4.1.2
	 * Dot-string = Atom *("." Atom)
	 * Atom = 1*atext
	 */
	public static function isDotString(string $str, bool $smtputf8 = false): bool
	{
		if (!$str) {
			return false;
		}
		$parts = explode('.', $str);
		foreach ($parts as $part) {
			if ($part === '') {
				return false;
			}
			if (!self::isAText($part, $smtputf8)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * RFC 5322 § 3.2.3
	 * atext = ALPHA / DIGIT /
	 *  "!" / "#" / "$" / "%" / "&" "*" / "+" /
	 *  "-" / "/" / "=" / "?" / "^" / "_" / "`" / "{" / "|" / "}" / "~"
	 */
	public static function isAText(string $str, bool $smtputf8 = false): bool
	{
		if ($smtputf8) {
			return ! preg_match('/[\x00-\x1F\x7F()<>@.,;:\\\\"\[\] ]/', $str);
		}
		return ! preg_match('/[\x00-\x1F\x7F()<>@.,;:\\\\"\[\] \x80-\xFF]/', $str);
	}

}
