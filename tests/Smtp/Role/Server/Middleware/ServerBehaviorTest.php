<?php

namespace Tap\Smtp\Test\Role\Server;

use PHPUnit\Framework\TestCase;
use Tap\ReflectiveTap;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\DataStream;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Reply\ReplyFactory;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Role\Agent\Action\NewSession;
use Tap\Smtp\Role\Agent\Agent;
use Tap\Smtp\Role\Client\Action\ReceiveCommandReply;
use Tap\Smtp\Role\Server\Action\ReceiveCommand;
use Tap\Smtp\Role\Server\Action\ReceiveMail;
use Tap\Smtp\Role\Server\Action\SendCommandReply;
use Tap\Smtp\Role\Server\Middleware\ServerBehavior;
use Tap\Smtp\Session\Session;

class ServerBehaviorTest extends TestCase
{
	public function testMailTransaction()
	{
		$domain = new Domain('ducksarentreal.mil');
		$server = new ServerBehavior($domain);
		$mailbox = new InMemoryMailbox();
		$agent = new Agent($server, $mailbox);
		$session = new Session('id');
		$agent->dispatch(
			new NewSession($session),
		);
		$helo = new Helo($domain);
		$from = new ReversePath(new Mailbox('a', $domain));
		$tos = [new ForwardPath(new Mailbox('b', $domain))];
		$dataStream = 'test';

		// HELO
		$agent->dispatch(new ReceiveCommand($helo));

		// MAIL FROM
		$agent->dispatch(new ReceiveCommand(new MailFrom($from)));

		// RCPT TO
		foreach ($tos as $to) {
			$agent->dispatch(new ReceiveCommand(new RcptTo($to)));
		}
		$this->assertCount(1, $session->rcptTos);

		// DATA
		$agent->dispatch(new ReceiveCommand(new Data()));

		// .
		$agent->dispatch(new ReceiveCommand(new DataStream('test')));

		// QUIT
		$agent->dispatch(new ReceiveCommand(new Quit()));

		$this->assertCount(1, $mailbox->mailbox);
		$this->assertEquals(
			new ReceiveMail($from, $tos, $dataStream),
			$mailbox->mailbox[0]
		);
	}
}

class InMemoryMailbox extends ReflectiveTap
{
	/**
	 * @var ReceiveMail[]
	 */
	public array $mailbox = [];
	public function receiveMail(ReceiveMail $action): void
	{
		$this->mailbox[] = $action;
		$this->next($action);
	}

	public function sendCommandReply(SendCommandReply $action): void
	{
		$command = $action->command;

		if ($command instanceof RcptTo) {
			$action->reply = ReplyFactory::ok();
		}
		$this->next($action);
	}
}
