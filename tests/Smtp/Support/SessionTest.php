<?php

namespace Tap\Smtp\Test\Role\Client;

use PHPUnit\Framework\TestCase;
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
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\EhloReply;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Session\Session;

class SessionTest extends TestCase
{
	public function testAwaitingReply()
	{
		$replyOk = new GenericReply(new Code('250'), ['Ok']);
		$rcptTos = [
			new RcptTo(new ForwardPath(new Mailbox('harry', new Domain('dunne.name')))),
			new RcptTo(new ForwardPath(new Mailbox('lloyd', new Domain('christmas.name')))),
		];
		$session = new Session('🎵');
		$this->assertFalse($session->awaitingReply());
		$this->assertFalse($session->awaitingReply(), 'stray reply');
		foreach ($rcptTos as $rcptTo) {
			$session->receiveCommand($rcptTo);
		}
		$this->assertTrue($session->awaitingReply());
		$session->receiveReply($replyOk);
		$this->assertTrue($session->awaitingReply());
		$session->receiveReply($replyOk);
		$this->assertFalse($session->awaitingReply());
	}

	public function testSaidHello()
	{
		$origin = new Domain('norm.macdonald.name');
		$ehlo = new Ehlo($origin);
		$helo = new Helo($origin);
		$replyOk = new GenericReply(Code::ehloOk(), ['Ok']);
		$replyEhloOk = new EhloReply(Code::ehloOk(), $origin, 'Howdy cowboy', []);
		$replyEhloFail = new GenericReply(new Code('500'), ['Command not recognized']);

		$session = new Session('Empty');
		$this->assertFalse($session->saidHello());

		$session = new Session('ESMTP');
		$session->receiveCommand($ehlo);
		$session->receiveReply($replyEhloOk);
		$this->assertTrue($session->saidHello());
		$session->receiveCommand(new Rset());
		$session->receiveReply($replyOk);
		$this->assertTrue($session->saidHello(), 'resetting does not remove hello');

		$session = new Session('SMTP');
		$session->receiveCommand($ehlo);
		$session->receiveReply($replyEhloFail);
		$this->assertFalse($session->saidHello(), 'EHLO fails');
		$session->receiveCommand($helo);
		$session->receiveReply($replyOk);
		$this->assertTrue($session->saidHello(), 'HELO ok');
	}

	public function testIsEsmtp()
	{
		$origin = new Domain('norm.macdonald.name');
		$ehlo = new Ehlo($origin);
		$helo = new Helo($origin);
		$replyOk = new GenericReply(Code::ehloOk(), ['Ok']);
		$replyEhloOk = new EhloReply(Code::ehloOk(), $origin, 'Howdy cowboy', []);
		$replyEhloFail = new GenericReply(new Code('500'), ['Command not recognized']);

		$session = new Session('Empty');
		$this->assertFalse($session->isEsmtp(), 'session is not esmtp by default');

		$session = new Session('ESMTP');
		$session->receiveCommand($ehlo);
		$session->receiveReply($replyEhloOk);
		$this->assertTrue($session->isEsmtp());
		$session->receiveCommand(new Rset());
		$session->receiveReply($replyOk);
		$this->assertTrue($session->isEsmtp(), 'resetting does not remove esmtp');

		$session = new Session('SMTP');
		$session->receiveCommand($ehlo);
		$session->receiveReply($replyEhloFail);
		$this->assertFalse($session->isEsmtp(), 'EHLO fails, not esmtp');
		$session->receiveCommand($helo);
		$session->receiveReply($replyOk);
		$this->assertFalse($session->isEsmtp(), 'HELO ok, not esmtp');
	}

  public function testMailTransaction()
  {
		// Greeting
		$sessionId = '💩';
		$origin = new Domain('norm.macdonald.name');
		$greeting = new Greeting($origin);
		$session = new Session($sessionId);
		$session->receiveGreeting($greeting);
		$this->assertEquals(
			$greeting,
			$session->greeting,
			'Greeting was stored in session state',
		);

		// Ehlo
		$ehlo = new Ehlo($origin);
		$session->receiveCommand($ehlo);
		$this->assertEmpty(
			$session->ehlo,
			'Session state should not accept EHLO until a successful reply'
		);
		$replyEhloOk = new EhloReply(Code::ehloOk(), $origin, 'Howdy cowboy', []);
		$session->receiveReply($replyEhloOk);
		$this->assertEquals(
			$ehlo,
			$session->ehlo,
			'Successful reply registered EHLO into session state'
		);

		// MailFrom
		$from = new Mailbox('harry', new Domain('dunne.name'));
		$tos = [new Mailbox('lloyd', new Domain('christmas.name'))];
		$reversePath = new ReversePath($from);
		$forwardPaths = [new ForwardPath($tos[0])];
		$mailFrom = new MailFrom($reversePath, $forwardPaths);
		$session->receiveCommand($mailFrom);
		$this->assertEmpty($session->mailFrom);
		$replyOk = new GenericReply(new Code('250'), ['Ok']);
		$session->receiveReply($replyOk);
		$this->assertEquals(
			$mailFrom,
			$session->mailFrom,
		);

		// RcptTo
		$rcptTos = [];
		foreach ($tos as $to) {
			$rcptTos[] = $rcptTo = new RcptTo(new ForwardPath($to));
			$session->receiveCommand($rcptTo);
			$session->receiveReply($replyOk);
		}
		$this->assertEquals($rcptTos, $session->rcptTos);

		// Data
		$data = new Data();
		$replyCont = new GenericReply(Code::dataOk(), ['End data with <CR><LF>.<CR><LF>']);
		$session->receiveCommand($data);
		$session->receiveReply($replyCont);
		$this->assertSame($data, $session->data);

		// .
		$endOfData = new EndOfData();
		$session->receiveCommand($endOfData);
		$session->receiveReply($replyOk);
		$this->assertSame($endOfData, $session->endOfData);

		// Quit
		$quit = new Quit();
		$session->receiveCommand($quit);
		$session->receiveReply($replyOk);
		$this->assertSame($quit, $session->quit);
  }
}

