<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;


/**
 * RFC 5321 § 4.1.1.1. Extended HELLO (EHLO) or HELLO (HELO)
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
 */
class EhloKeywordBase implements EhloKeyword
{
	public function __construct(
		public string $name,

		/**
		 * @var EhloParam[]
		 */
		public array $params = null,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return EhloParam[]
	 */
	public function getParams(): array
	{
		return $this->params;
	}
}

