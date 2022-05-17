<?php

namespace Tap\State;

use Tap\ActionInterface;
use Tap\Middleware;
use Tap\MiddlewareInterface;

class State implements MiddlewareInterface
{
  public function __invoke(ActionInterface $action)
  {
  }
}
