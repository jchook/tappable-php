<?php

namespace Tap\Smtp\Role\Client\Middleware;

use Tap\ReflectiveTap;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Role\Client\Action\NewSession;
use Tap\Smtp\Role\Client\Action\ReceiveCommandReply;
use Tap\Smtp\Role\Client\Action\ReceiveGreeting;
use Tap\Smtp\Role\Client\Action\SendCommand;
use Tap\Smtp\Role\Client\Exception\ClientSpokeTooEarly;
use Tap\Smtp\Support\Session;

class ClientBehavior extends ReflectiveTap
{
  const DEFAULT_TRANSACTION_ID = 'default';

  public function __construct(
    public Origin $origin,
    public Session $session = new Session(self::DEFAULT_TRANSACTION_ID),
  )
  {
  }

  protected function receiveGreeting(ReceiveGreeting $action): void
  {
    $this->next($action);
    $this->session->receiveReply($action->greeting);
    $this->dispatch(
      new SendCommand(
        new Ehlo($this->origin)
      )
    );
  }

  protected function newSession(NewSession $action): void
  {
    $this->session = $action->session;
    $this->next($action);
  }

  protected function receiveCommandReply(ReceiveCommandReply $action): void
  {
    $this->next($action);
    $this->session->receiveReply($action->reply);
  }

  protected function sendCommand(SendCommand $action): void
  {
    //
    if (!$this->session->getState()->greeting) {
      throw new ClientSpokeTooEarly(
        'Protocol error: client attempted to send a ' .
        $action->command->getVerb() . ' command before receiving server greeting.'
      );
    }
    $this->next($action);
    $this->session->receiveCommand($action->command);
  }
}


