<?php

namespace Tap\Smtp\Test\Role\Client;

use PHPUnit\Framework\TestCase;
use Tap\Action;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Role\Client\Action\ReceiveCommandReply;
use Tap\Smtp\Role\Client\Action\ReceiveGreeting;
use Tap\Smtp\Role\Client\Action\SendCommand;
use Tap\Smtp\Role\Client\ClientAgent;
use Tap\Smtp\Role\Client\Exception\ClientSpokeTooEarly;
use Tap\TapBase;

class ClientAgentTest extends TestCase
{
	public function getServerDetails(): ServerDetails
	{
		return new ServerDetails(
			new Domain('ducks.gov')
		);
	}

	public function testSpokeTooEarly()
	{
		$this->expectException(ClientSpokeTooEarly::class);
    $client = new ClientAgent();
		$client->dispatch(new SendCommand(new Helo(new Domain('tomato.org'))));
	}

  public function testBasicTransaction()
  {
		$server = $this->getServerDetails();
    $client = new ClientAgent();

		// Greeting
		$greeting = new Greeting($server->origin);
		$client->dispatch(new ReceiveGreeting($greeting));
		$this->assertEquals(
			$greeting,
			$client->smtp->session->state->greeting,
			'Greeting was stored in session state',
		);

		// Ehlo
		$ehlo = new Ehlo($client->origin);
		$client->dispatch(new SendCommand($ehlo));
		$this->assertEmpty(
			$client->smtp->session->state->ehlo,
			'Session state should not accept EHLO until a successful reply'
		);
		$client->dispatch(new ReceiveCommandReply(
			$ehlo,
			new GenericReply(new Code('220'), ['Ok'])
		));
		$this->assertEquals(
			$ehlo,
			$client->smtp->session->state->ehlo,
			'Successful reply registered EHLO into session state'
		);

		// MailFrom
		$from = new Mailbox('harry', new Domain('dunne.name'));
		$tos = [new Mailbox('lloyd', new Domain('christmas.name'))];
		$reversePath = new ReversePath($from);
		$forwardPaths = [new ForwardPath($tos[0])];
		$mailFrom = new MailFrom($reversePath, $forwardPaths);
		$client->dispatch(new SendCommand($mailFrom));
		$this->assertEmpty($client->smtp->session->state->mailFrom);
		$client->dispatch(new ReceiveCommandReply(
			$mailFrom,
			new GenericReply(new Code('220'), ['Ok'])
		));
		// TODO: This should all be a SessionState test
		// $this->assertEquals(
		// 	$mailFrom,
		// 	$client->smtp->session->state->mailFrom,
		// );
  }
}

class InspectorTap extends TapBase {
	public $actions = [];
	public function __invoke(Action $action)
	{
		$this->next($action);
		$this->actions[] = $action;
	}
}

class ServerDetails {
	public function __construct(
		public Origin $origin,
	)
	{
	}
}
