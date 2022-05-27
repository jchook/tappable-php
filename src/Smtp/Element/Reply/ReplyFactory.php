<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

use Tap\Smtp\Element\Origin\Domain;

class ReplyFactory
{
	public static function ok(array $messages = ['Ok']): Reply
	{
		return new GenericReply(Code::ok(), $messages);
	}

	public static function ehloOk(Domain $domain): EhloReply
	{
		return new EhloReply(Code::ok(), $domain, 'Hi :)', [
			new EhloKeywordBase('SMTPUTF8'),
		]);
	}

	public static function heloOk(Domain $domain)
	{
		return self::ok([idn_to_ascii($domain->domain)]);
	}

	public static function syntaxError(array $messages = ['Syntax error']): Reply
	{
		return new GenericReply(new Code('500'), $messages);
	}

	public static function commandUnrecognized(array $messages = ['Command unrecognized']): Reply
	{
		return new GenericReply(new Code('500'), $messages);
	}

	public static function syntaxErrorInParams(array $messages = ['Syntax error in parameters or arguments']): Reply
	{
		return new GenericReply(new Code('501'), $messages);
	}

	public static function commandNotImplemented(array $messages = ['Command not implemented']): Reply
	{
		return new GenericReply(new Code('502'), $messages);
	}

	public static function badSequence(array $messages = ['Bad sequence of commands']): Reply
	{
		return new GenericReply(new Code('503'), $messages);
	}

	public static function commandParamNotImplemented(array $messages = ['Command parameter not implemented']): Reply
	{
		return new GenericReply(new Code('504'), $messages);
	}

	public static function systemStatus(array $messages = ['System status, or system help reply']): Reply
	{
		return new GenericReply(new Code('211'), $messages);
	}

	public static function helpMessage(array $messages = ['Help message']): Reply
	{
		return new GenericReply(new Code('214'), $messages);
	}

	public static function serviceReady(array $messages = ['<domain> Service ready']): Reply
	{
		return new GenericReply(new Code('220'), $messages);
	}

	public static function serviceClosingChannel(array $messages = ['<domain> Service closing transmission channel']): Reply
	{
		return new GenericReply(new Code('221'), $messages);
	}

	public static function serviceNotAvailable(array $messages = ['<domain> Service not available, closing transmission channel']): Reply
	{
		return new GenericReply(new Code('421'), $messages);
	}

	public static function userNotLocalWillFwd(array $messages = ['User not local; will forward to <forward-path>']): Reply
	{
		return new GenericReply(new Code('251'), $messages);
	}

	public static function cannotVrfy(array $messages = ['Cannot VRFY user, but will accept message and attempt delivery']): Reply
	{
		return new GenericReply(new Code('252'), $messages);
	}

	public static function unableToAccommodateParams(array $messages = ['Server unable to accommodate parameters']): Reply
	{
		return new GenericReply(new Code('455'), $messages);
	}

	public static function mailOrRcptParamNotRecognized(array $messages = ['MAIL FROM/RCPT TO parameters not recognized or not implemented']): Reply
	{
		return new GenericReply(new Code('555'), $messages);
	}

	public static function mailboxTempUnavailable(array $messages = ['Requested mail action not taken: mailbox unavailable']): Reply
	{
		return new GenericReply(new Code('450'), $messages);
	}

	public static function mailboxUnavailable(array $messages = ['Requested action not taken: mailbox unavailable']): Reply
	{
		return new GenericReply(new Code('550'), $messages);
	}

	public static function actionAborted(array $messages = ['Requested action aborted: error in processing']): Reply
	{
		return new GenericReply(new Code('451'), $messages);
	}

	public static function userNotLocal(array $messages = ['User not local; please try <forward-path>']): Reply
	{
		return new GenericReply(new Code('551'), $messages);
	}

	public static function insufficientStorage(array $messages = ['Requested action not taken: insufficient system storage']): Reply
	{
		return new GenericReply(new Code('452'), $messages);
	}

	public static function exceededStorageAllocation(array $messages = ['Requested mail action aborted: exceeded storage allocation']): Reply
	{
		return new GenericReply(new Code('552'), $messages);
	}

	public static function mailboxNameNotAllowed(array $messages = ['Requested action not taken: mailbox name not allowed']): Reply
	{
		return new GenericReply(new Code('553'), $messages);
	}

	public static function startMailInput(array $messages = ['Start mail input']): Reply
	{
		return new GenericReply(new Code('354'), $messages);
	}

	public static function transactionFailed(array $messages = ['Transaction failed']): Reply
	{
		return new GenericReply(new Code('554'), $messages);
	}
}

