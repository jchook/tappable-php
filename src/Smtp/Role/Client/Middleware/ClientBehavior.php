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
    public Session $txn = new Session(self::DEFAULT_TRANSACTION_ID),
  )
  {
  }

  protected function handleGreeting(ReceiveGreeting $action): void
  {
    $this->next($action);
    $this->dispatch(
      new SendCommand(
        $this->txn,
        new Ehlo($this->origin)
      )
    );
  }

  public function handleNewSession(NewSession $action): void
  {
    $this->txn = $action->txn;
    $this->next($action);
  }

  public function handleReply(ReceiveCommandReply $action): void
  {
    $this->txn->receiveReply($action->reply);
    $this->next($action);
  }

  public function handleCommand(SendCommand $action): void
  {
    //
    if (!$this->txn->getState()->greeting) {
      throw new ClientSpokeTooEarly(
        'Protocol error: client attempted to send a ' .
        $action->command->getVerb() . ' command before receiving server greeting.'
      );
    }
    $this->txn->receiveCommand($action->command);
    $this->next($action);
  }
}


