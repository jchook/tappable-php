<?php

namespace Tap\Smtp\Middleware;

use Tap\Middleware;
use Tap\MiddlewareInterface;
use Tap\Smtp\Action\Command;

class Smtp implements MiddlewareInterface
{
  use Middleware;

  public function handleCommand(Command $action): void
  {
    $this->next($action);
  }
}

