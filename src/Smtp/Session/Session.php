<?php

namespace Tap\Smtp\Session;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\EndOfData;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Command\Rset;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Role\Client\Action\SendMail;

/**
 *
 * RFC 5321 § 2.3.6. Buffer and State Table
 *
 *   SMTP sessions are stateful, with both parties carefully maintaining a
 *   common view of the current state.  In this document, we model this
 *   state by a virtual "buffer" and a "state table" on the server that
 *   may be used by the client to, for example, "clear the buffer" or
 *   "reset the state table", causing the information in the buffer to be
 *   discarded and the state to be returned to some previous state.
 *
 * RFC 5321 § 4.2
 *
 *   Replies to SMTP commands serve to ensure the synchronization of
 *   requests and actions in the process of mail transfer and to guarantee
 *   that the SMTP client always knows the state of the SMTP server.
 *   Every command MUST generate exactly one reply.
 *
 * RFC 5321 § 4.3
 *
 *   The communication between the sender and receiver is an alternating
 *   dialogue, controlled by the sender.  As such, the sender issues a
 *   command and the receiver responds with a reply.  Unless other
 *   arrangements are negotiated through service extensions, the sender
 *   MUST wait for this response before sending further commands.  One
 *   important reply is the connection greeting.  Normally, a receiver
 *   will send a 220 "Service ready" reply when the connection is
 *   completed.  The sender SHOULD wait for this greeting message before
 *   sending any commands.
 */
class Session
{
	/**
	 * @var Command[]
	 */
	protected array $commandQueue = [];

	public function __construct(
		public string $id,
		public ?Greeting $greeting = null,
		public ?Helo $helo = null,
		public ?Ehlo $ehlo = null,
		public ?SendMail $sendMail = null,
		public ?MailFrom $mailFrom = null,
		/**
		 * Valid RCPT TO commands
		 * @var RcptTo[]
		 */
		public array $rcptTos = [],
		public ?Data $data = null,
		public ?EndOfData $endOfData = null,
		public ?Quit $quit = null,
	)
	{
	}

	public function awaitingReply(): bool
	{
		return !empty($this->commandQueue);
	}

	public function getCurrentCommand(): ?Command
	{
		return reset($this->commandQueue) ?: null;
	}

	public function isEsmtp()
	{
		return (bool) $this->ehlo;
	}

	public function reset()
	{
		$this->mailFrom = null;
		$this->rcptTos = [];
		$this->data = null;
		$this->endOfData = null;
	}

	public function saidHello(): bool
	{
		return $this->helo || $this->ehlo;
	}

	public function receiveCommand(Command $command): void
	{
		$this->commandQueue[] = $command;
	}

	public function receiveGreeting(Greeting $greeting): void
	{
		$this->greeting = $greeting;
	}

	/**
	 * Receive the reply into state and return the associated command
	 */
	public function receiveReply(Reply $reply): ?Command
	{
		$cmd = null;

		// The greeting does not belong to a command
		if ($reply instanceof Greeting) {
			$this->greeting = $reply;
		}

		// All other replies SHOULD have a command waiting in the queue
		// Stray replies may not break everything, but it is against the spec.
		else {
			$cmd = array_shift($this->commandQueue);
		}

		if ($reply->code->isPositive()) {
			if ($cmd instanceof Rset) {
				$this->reset();
			}
			elseif ($cmd instanceof Helo) {
				$this->reset();
				$this->ehlo = null;
				$this->helo = $cmd;
			}

			/**
			 * RFC 5321 § 4.1.4
			 *
			 *   An EHLO command MAY be issued by a client later in the session.  If
			 *   it is issued after the session begins and the EHLO command is
			 *   acceptable to the SMTP server, the SMTP server MUST clear all buffers
			 *   and reset the state exactly as if a RSET command had been issued.  In
			 *   other words, the sequence of RSET followed immediately by EHLO is
			 *   redundant, but not harmful other than in the performance cost of
			 *   executing unnecessary commands.
			 */
			elseif ($cmd instanceof Ehlo) {
				$this->reset();
				$this->ehlo = $cmd;
				$this->helo = null;
			}

			/**
			 * RFC 5321 § 4.1.1.2
			 *
			 *  This command clears the reverse-path buffer, the forward-path buffer,
			 *  and the mail data buffer, and it inserts the reverse-path information
			 *  from its argument clause into the reverse-path buffer.
			 */
			elseif ($cmd instanceof MailFrom) {
				$this->mailFrom = $cmd;
				$this->rcptTos = [];
				$this->data = null;
			}
			elseif ($cmd instanceof RcptTo) {
				$this->rcptTos[] = $cmd;
			}
			elseif ($cmd instanceof Data) {
				$this->data = $cmd;
			}
			elseif ($cmd instanceof EndOfData) {
				$this->endOfData = $cmd;
			}
			elseif ($cmd instanceof Quit) {
				$this->quit = $cmd;
			}
		}

		return $cmd;
	}
}

