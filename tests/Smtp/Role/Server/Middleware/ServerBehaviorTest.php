<?php

namespace Tap\Smtp\Test\Role\Server;

use PHPUnit\Framework\TestCase;
use Tap\ReflectiveTap;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\EndOfData;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Command\Rset;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Reply\ReplyFactory;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Role\Agent\Action\NewSession;
use Tap\Smtp\Role\Agent\Agent;
use Tap\Smtp\Role\Server\Action\ReceiveCommand;
use Tap\Smtp\Role\Server\Action\ReceiveMail;
use Tap\Smtp\Role\Server\Action\ReceiveMailData;
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
		$mailFrom = new MailFrom(new ReversePath(new Mailbox('a', $domain)));
		$rcptTos = [new RcptTo(new ForwardPath(new Mailbox('b', $domain)))];
		$dataStream = 'test';

		// HELO
		$agent->dispatch(new ReceiveCommand($helo));

		// MAIL FROM
		$agent->dispatch(new ReceiveCommand($mailFrom));

		// RCPT TO
		foreach ($rcptTos as $rcptTo) {
			$agent->dispatch(new ReceiveCommand($rcptTo));
		}

		// DATA
		$agent->dispatch(new ReceiveCommand(new Data()));
		$agent->dispatch(new ReceiveMailData($dataStream));

		// .
		$agent->dispatch(new ReceiveCommand(new EndOfData()));

		// QUIT
		$agent->dispatch(new ReceiveCommand(new Quit()));

		$this->assertCount(1, $mailbox->mailbox);
		$this->assertEquals(
			new ReceiveMail($mailFrom, $rcptTos, $dataStream),
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
	public ?ReceiveMail $receiveMail = null;
	public function receiveMailData(ReceiveMailData $action): void
	{
		$this->receiveMail->dataStream = $action->dataStream;
		$this->next($action);
	}
  public function sendCommandReply(SendCommandReply $action): void
  {
    $command = $action->command;
		if ($command instanceof MailFrom) {
			$this->receiveMail = new ReceiveMail();
			$this->receiveMail->mailFrom = $command;
		}
    elseif ($command instanceof RcptTo) {
      $action->reply = ReplyFactory::ok();
			$this->receiveMail->rcptTos[] = $command;
    }
		elseif ($command instanceof EndOfData) {
			$this->mailbox[] = $this->receiveMail;
			$this->receiveMail = null;
		}
		elseif (
			$command instanceof Ehlo ||
			$command instanceof Helo ||
			$command instanceof Rset ||
			$command instanceof Quit
		) {
			$this->receiveMail = null;
		}
    $this->next($action);
  }
}
