<?php

namespace Tap\Smtp\Support;

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

/**
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
class Transaction
{
	/**
	 * @var Command[]
	 */
	protected array $commandQueue = [];

	/**
	 * @var CommandReplyPair[]
	 */
	protected array $transcript = [];

	protected TransactionState $state;

	public function __construct(
		public string $id,
	)
	{
		$this->state = new TransactionState();
	}

	public function receiveCommand(Command $command)
	{
		$this->commandQueue[] = $command;
	}

	public function receiveGreeting(Greeting $greeting)
	{
		$this->greeting = $greeting;
		$this->transcript[] = new CommandReplyPair(null, $greeting);
	}

	public function getTranscript(): array
	{
		return $this->transcript;
	}

	public function getState(): TransactionState
	{
		return $this->state;
	}

	public function receiveReply(Reply $reply)
	{
		$cmd = null;

		// The greeting does not belong to a command
		if ($reply instanceof Greeting) {
			$this->state->greeting = $reply;
		}

		// All other replies SHOULD have a command waiting in the queue
		// Stray replies may not break everything, but it is against the spec.
		else {
			$cmd = array_shift($this->commandQueue);
		}

		$this->transcript[] = new CommandReplyPair($cmd, $reply);

		if ($reply->code->isPositive()) {
			if ($cmd instanceof Rset) {
				$this->state->reset();
			}
			elseif ($cmd instanceof Helo) {
				$this->state->helo = $cmd;
			}
			elseif ($cmd instanceof Ehlo) {
				$this->state->ehlo = $cmd;
			}
			elseif ($cmd instanceof MailFrom) {
				$this->state->mailFrom = $cmd;
			}
			elseif ($cmd instanceof RcptTo) {
				$this->state->rcptTos[] = $cmd;
			}
			elseif ($cmd instanceof Data) {
				$this->state->data = $cmd;
			}
			elseif ($cmd instanceof EndOfData) {
				$this->state->endOfData = $cmd;
			}
			elseif ($cmd instanceof Quit) {
				$this->state->quit = $cmd;
			}
		}
	}
}
