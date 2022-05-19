<?php

namespace Tap\Smtp\Support;

use Tap\Smtp\Element\Command\Command;
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
class CommandReplyPair
{
	public function __construct(
		public ?Command $command = null,
		public ?Reply $reply = null,
	)
	{
	}
}

