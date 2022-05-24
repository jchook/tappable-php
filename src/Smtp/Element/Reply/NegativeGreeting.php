<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

use Tap\Smtp\Element\Origin\Origin;

/**
 * RFC 5321 § 3.1. Session Initiation
 *
 *   The SMTP protocol allows a server to formally reject a mail session
 *   while still allowing the initial connection as follows: a 554
 *   response MAY be given in the initial connection opening message
 *   instead of the 220.  A server taking this approach MUST still wait
 *   for the client to send a QUIT (see Section 4.1.1.10) before closing
 *   the connection and SHOULD respond to any intervening commands with
 *   "503 bad sequence of commands".  Since an attempt to make an SMTP
 *   connection to such a system is probably in error, a server returning
 *   a 554 response on connection opening SHOULD provide enough
 *   information in the reply text to facilitate debugging of the sending
 *   system.
 */
class NegativeGreeting extends Greeting
{
	public Code $code;
	public function __construct(
		public ?Origin $origin = null,
		public array $messages = [],
	)
	{
		$this->code = new Code('554');
	}

	public function getCode(): Code
	{
		return $this->code;
	}
}


