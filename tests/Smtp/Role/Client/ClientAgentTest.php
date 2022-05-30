<?php

namespace Tap\Smtp\Test\Role\Client;

use PHPUnit\Framework\TestCase;
use Tap\Action;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Role\Client\Action\ReceiveGreeting;
use Tap\Smtp\Role\Client\Action\ReceiveReply;
use Tap\Smtp\Role\Client\Action\SendCommand;
use Tap\Smtp\Role\Client\Action\SendMail;
use Tap\Smtp\Role\Client\ClientAgent;
use Tap\Smtp\Textual\Renderer;
use Tap\TapBase;

class ClientAgentTest extends TestCase
{
	public function getServerDetails(): ServerDetails
	{
		return new ServerDetails(
			new Domain('ducks.gov')
		);
	}

	public function replyOk(): ReceiveReply
	{
		return new ReceiveReply(new GenericReply(Code::ok(), ['Ok']));
	}

	public function getClientReady(...$userTaps)
	{
    $client = new ClientAgent(null, null, ...$userTaps);
		$server = $this->getServerDetails();

		// Greeting
		$greeting = new Greeting($server->origin);
		$client->dispatch(new ReceiveGreeting($greeting));
		$this->assertNotEmpty($client->getSession()->greeting);

		// Ehlo
		// TODO: Should the client automatically say ehlo? probably..
		// but I think we want to separate the SessionAware behavior into a
		// separate Tap
		//
		// $ehlo = new Ehlo($client->origin);
		// $client->dispatch(new SendCommand($ehlo));
		$client->dispatch($this->replyOk());
		// $this->assertNotEmpty($client->getSession()->ehlo);

		return $client;
	}

  public function testSendMail()
  {
		$inspect = new InspectorTap();
		$client = $this->getClientReady($inspect);
		$from = new ReversePath(new Mailbox('harry', new Domain('dunne.name')));
		$tos = [
			new ForwardPath(new Mailbox('lloyd', new Domain('christmas.name'))),
			new ForwardPath(new Mailbox('mary', new Domain('swanson.name'))),
		];
		$data = 'test';
		$client->dispatch(new SendMail($from, $tos, $data));

		// MAIL FROM
		$this->assertEmpty($client->getSession()->mailFrom);
		$client->dispatch($this->replyOk());
		$this->assertNotEmpty($client->getSession()->mailFrom);

		// RCPT TO x2
		$this->assertEmpty($client->getSession()->rcptTos);
		$client->dispatch($this->replyOk());
		$this->assertNotEmpty($client->getSession()->rcptTos);
		$client->dispatch($this->replyOk());
		$this->assertCount(2, $client->getSession()->rcptTos);

		// DATA
		$this->assertEmpty($client->getSession()->data);
		$client->dispatch($this->replyOk());
		$this->assertNotEmpty($client->getSession()->data);

		// .
		$this->assertEmpty($client->getSession()->dataStream);
		$client->dispatch($this->replyOk());
		$this->assertNotEmpty($client->getSession()->dataStream);
  }
}

class InspectorTap extends TapBase {
	public $actions = [];
	public function __invoke(Action $action)
	{
		$this->next($action);
		$this->actions[] = $action;

		// Turn this on when you need to debug
		// echo $this->renderAction($action);
	}

	public function renderAction(Action $action)
	{
		$renderer = new Renderer(true);
		$str = [];
		if ($action instanceof SendCommand) {
			$str[] = $renderer->renderCommand($action->command);
		}
		if ($action instanceof ReceiveReply) {
			$str[] = $renderer->renderReply($action->reply);
		}
		return implode(' ', $str);
	}
}

class ServerDetails {
	public function __construct(
		public Origin $origin,
	)
	{
	}
}

