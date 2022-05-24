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
use Tap\Smtp\Support\CommandReplyPair;

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
interface Session
{
	// TODO: need to standardize how we check the current "stage" of a session
	// For example, I should be able to determine:
	//
	// - Did the server send us a 220 greeting?
	//   - In which case, we should send an EHLO
	// - Did the server send us a 554 greeting?
	//   - In which case we should QUIT and abort the session
	// - Did the server respond positively to EHLO?
	//   - If not we should send a HELO instead
	// - Did we get a positive reply to one of the Hello requests?
	// - Did we send a MAIL FROM yet? Did we get a positive reply?
	// - Did we send any RCTP TO commands yet?
	// - Did any of the RCPT TO commands get positive replies?
	// - Which RCPT TO commands got negative replies?
	// - etc
	//
	// I think the simplest way is to have pairs for each one:
	// - getGreeting()
	// - getEhlo()
	// - getEhloReply()
	// - getHelo()
	// - getHeloReply()
	// - getMailFrom()
	// - getMailFromReply()
	// - getRcptTos()
	// - getRcptToReplies()
	// - getData()
	// - getDataReply()
	// - getEndOfData()
	// - getEndOfDataReply()
	// - getQuit()
	// - getQuitReply()
	public function isAwaitingReply(): bool;
	public function isEsmtp(): bool;
	public function reset(): void;
	public function saidHello(): bool;

	/**
	 * @return CommandReplyPair[]
	 */
	public function getTranscript(): array;
	public function prepareToSendMail(SendMail $sendMail): void;
	public function receiveCommand(Command $command): void;
	public function receiveGreeting(Greeting $greeting): void;
	public function receiveReply(Reply $reply): void;
	public function receiveStrayReply(Reply $reply): void;
}


