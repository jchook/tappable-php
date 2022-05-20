<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

use Tap\Smtp\Element\Origin;

/**
 *
 *    In ABNF, server responses are:
 *
 *    Greeting       = ( "220 " (Domain / address-literal)
 *                   [ SP textstring ] CRLF ) /
 *                   ( "220-" (Domain / address-literal)
 *                   [ SP textstring ] CRLF
 *                   *( "220-" [ textstring ] CRLF )
 *                   "220" [ SP textstring ] CRLF )
 *
 *    textstring     = 1*(%d09 / %d32-126) ; HT, SP, Printable US-ASCII
 */
class Greeting implements Reply
{
	public Code $code;
	public function __construct(
		public ?Origin $origin = null,
		public array $messages = [],
	)
	{
		$this->code = new Code(220);
	}

	public function getCode(): Code
	{
		return $this->code;
	}
}

