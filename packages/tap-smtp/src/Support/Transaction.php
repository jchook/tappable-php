<?php

namespace Tap\Smtp\Support;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\EndOfData;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Exception\IOException;

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
	public function __construct(
		public string $id,
	)
	{
	}

	public function receiveCommand(Command $command)
	{
    if ($command instanceof RcptTo) {
      $this->addRctpTo($command);
    }
    elseif ($command instanceof Greeting) {
      $this->setGreeting($command);
    }
    elseif ($command instanceof MailFrom) {
      $this->setMailFrom($command);
    }
    elseif ($command instanceof Data) {
      $this->setData($command);
    }
	}

	public function reset(): void
	{
		$this->greeting = null;
		$this->mailFrom = null;
		$this->rcptTos = [];
		$this->data = null;
		$this->endOfData = null;
		$this->closeDataStream();
	}

	public function closeDataStream(): ?bool
	{
		$dataStream = $this->dataStream;
		$this->dataStream = null;
		if ($dataStream) {
			return fclose($dataStream);
		}
		return null;
	}

	public function setGreeting(Greeting $greeting)
	{
		$this->greeting = $greeting;
	}

	public function setMailFrom(MailFrom $mailFrom)
	{
		$this->mailFrom = $mailFrom;
	}

	public function addRctpTo(RcptTo $rcptTo)
	{
		$this->rcptTos[] = $rcptTo;
	}

	public function setData(Data $data)
	{
		$this->data = $data;
		$this->dataStream = fopen('php://temp', 'w+');
	}

	public function writeData(string $data): int
	{
		$result = fwrite($this->dataStream, $data);
		if ($result === false) {
			throw new IOException('Unable to write to the transaction data stream');
		}
		return $result;
	}
}
