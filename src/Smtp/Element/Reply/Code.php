<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

/**
 *
 * 4.2. SMTP Replies
 *
 *   In particular, the 220, 221, 251, 421, and 551 reply codes
 *   are associated with message text that must be parsed and interpreted
 *   by machines.
 *
 * 4.2.1.  Reply Code Severities and Theory
 *
 *   The three digits of the reply each have a special significance.  The
 *   first digit denotes whether the response is good, bad, or incomplete.
 *   An unsophisticated SMTP client, or one that receives an unexpected
 *   code, will be able to determine its next action (proceed as planned,
 *   redo, retrench, etc.) by examining this first digit.  An SMTP client
 *   that wants to know approximately what kind of error occurred (e.g.,
 *   mail system error, command syntax error) may examine the second
 *   digit.  The third digit and any supplemental information that may be
 *   present is reserved for the finest gradation of information.
 *
 *   There are four values for the first digit of the reply code:
 *
 *   2yz  Positive Completion reply
 *      The requested action has been successfully completed.  A new
 *      request may be initiated.
 *
 *   3yz  Positive Intermediate reply
 *      The command has been accepted, but the requested action is being
 *      held in abeyance, pending receipt of further information.  The
 *      SMTP client should send another command specifying this
 *      information.  This reply is used in command sequence groups (i.e.,
 *      in DATA).
 *
 *   4yz  Transient Negative Completion reply
 *      The command was not accepted, and the requested action did not
 *      occur.  However, the error condition is temporary, and the action
 *      may be requested again.  The sender should return to the beginning
 *      of the command sequence (if any).  It is difficult to assign a
 *      meaning to "transient" when two different sites (receiver- and
 *      sender-SMTP agents) must agree on the interpretation.  Each reply
 *      in this category might have a different time value, but the SMTP
 *      client SHOULD try again.  A rule of thumb to determine whether a
 *      reply fits into the 4yz or the 5yz category (see below) is that
 *      replies are 4yz if they can be successful if repeated without any
 *      change in command form or in properties of the sender or receiver
 *      (that is, the command is repeated identically and the receiver
 *      does not put up a new implementation).
 *
 *   5yz  Permanent Negative Completion reply
 *      The command was not accepted and the requested action did not
 *      occur.  The SMTP client SHOULD NOT repeat the exact request (in
 *      the same sequence).  Even some "permanent" error conditions can be
 *      corrected, so the human user may want to direct the SMTP client to
 *      reinitiate the command sequence by direct action at some point in
 *      the future (e.g., after the spelling has been changed, or the user
 *      has altered the account status).
 *
 *   It is worth noting that the file transfer protocol (FTP) [34] uses a
 *   very similar code architecture and that the SMTP codes are based on
 *   the FTP model.  However, SMTP uses a one-command, one-response model
 *   (while FTP is asynchronous) and FTP's 1yz codes are not part of the
 *   SMTP model.
 *
 *   The second digit encodes responses in specific categories:
 *
 *   x0z  Syntax: These replies refer to syntax errors, syntactically
 *      correct commands that do not fit any functional category, and
 *      unimplemented or superfluous commands.
 *
 *   x1z  Information: These are replies to requests for information, such
 *      as status or help.
 *
 *   x2z  Connections: These are replies referring to the transmission
 *      channel.
 *
 *   x3z  Unspecified.
 *
 *   x4z  Unspecified.
 *
 *   x5z  Mail system: These replies indicate the status of the receiver
 *      mail system vis-a-vis the requested transfer or other mail system
 *      action.
 *
 *   The third digit gives a finer gradation of meaning in each category
 *   specified by the second digit.  The list of replies illustrates this.
 *   Each reply text is recommended rather than mandatory, and may even
 *   change according to the command with which it is associated.  On the
 *   other hand, the reply codes must strictly follow the specifications
 *   in this section.  Receiver implementations should not invent new
 *   codes for slightly different situations from the ones described here,
 *   but rather adapt codes already defined.
 *
 *   For example, a command such as NOOP, whose successful execution does
 *   not offer the SMTP client any new information, will return a 250
 *   reply.  The reply is 502 when the command requests an unimplemented
 *   non-site-specific action.  A refinement of that is the 504 reply for
 *   a command that is implemented, but that requests an unimplemented
 *   parameter.
 *
 *
 */
class Code
{
	public function __construct(
		public string $value,
	)
	{
	}

	public function getSeverity(): string
	{
		return $this->value[0] ?? '';
	}

	public function isPositive(): bool
	{
		return in_array($this->getSeverity(), ['2', '3']);
	}

	public function isNegative(): bool
	{
		return in_array($this->getSeverity(), ['4', '5']);
	}

	public function isCompletion(): bool
	{
		return $this->getSeverity() === '2';
	}

	public function isIntermediate(): bool
	{
		return $this->getSeverity() === '3';
	}

	public function isTransient(): bool
	{
		return $this->getSeverity() === '4';
	}

	public function isPermanant(): bool
	{
		return $this->getSeverity() === '5';
	}

	public static function ok(): static
	{
		return new static('250');
	}

	public static function ehloOk(): static
	{
		return new static('220');
	}
}

