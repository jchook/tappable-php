<?php declare(strict_types=1);

namespace Tap\Smtp\Textual;

class Lexeme
{

	/**
	 * RFC 5321 § 4.1.2
	 * Dot-string = Atom *("." Atom)
	 * Atom = 1*atext
	 */
	public static function isDotString(string $str, bool $global = false): bool
	{
		if (!$str) {
			return false;
		}
		$parts = explode('.', $str);
		if ($parts[0] === '') {
			return false;
		}
		foreach ($parts as $part) {
			if (!self::isAText($part, $global)) {
				return false;
			}
		}
		if ($part === '') {
			return false;
		}
		return true;
	}

	/**
	 * RFC 5322 § 3.2.3
	 * atext = ALPHA / DIGIT /
	 *  "!" / "#" / "$" / "%" / "&" "*" / "+" /
	 *  "-" / "/" / "=" / "?" / "^" / "_" / "`" / "{" / "|" / "}" / "~"
	 */
	public static function isAText(string $str, bool $global = false): bool
	{
		if ($global) {
			return ! preg_match('/[\x00-\x1F\x7F()<>@.,;:\\\\"\[\] ]/', $str);
		}
		return ! preg_match('/[\x00-\x1F\x7F()<>@.,;:\\\\"\[\] \x80-\xFF]/', $str);
	}

}
