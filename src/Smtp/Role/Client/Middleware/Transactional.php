<?php

namespace Tap\Smtp\Middleware;

use Tap\Smtp\Action;
use Tap\TapBase;

class Transactional extends TapBase
{
  public function handleReply(Action\Reply $action): void
  {
    $action->txn->receiveReply($action->reply);
    $this->next($action);
  }

  public function handleCommand(Action\Command $action): void
  {
    $action->txn->receiveCommand($action->command);
    $this->next($action);
  }
}

