<?php

namespace Tap\Smtp\Middleware;

use Tap\Smtp\Action\Command;
use Tap\Smtp\Action\Reply;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\TapBase;

class Smtp extends TapBase
{
  public function handleReply(Reply $action): void
  {

  }

  public function rcpt(Command $action): void
  {
    $txn = $action->txn;
    $cmd = $action->command;
    if ($cmd instanceof RcptTo) {
      $txn->addRctpTo($cmd);
    }
    elseif ($cmd instanceof Greeting) {
      $txn->setGreeting($cmd);
    }
    elseif ($cmd instanceof MailFrom) {
      $txn->setMailFrom($cmd);
    }
    $this->next($action);
  }
}

