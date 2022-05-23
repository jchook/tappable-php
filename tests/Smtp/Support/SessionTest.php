<?php

namespace Tap\Smtp\Test\Role\Client;

use PHPUnit\Framework\TestCase;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Session\Session;

class SessionTest extends TestCase
{
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
		$replyOk = new GenericReply(new Code('220'), ['Ok']);
		$session->receiveReply($replyOk);
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
		$session->receiveReply($replyOk);
		// TODO: failing
		// $this->assertEquals(
		// 	$mailFrom,
		// 	$session->mailFrom,
		// );
  }
}

