<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Role\Agent\Action\AgentAction;

class ReceiveGreeting extends AgentAction
{
  public function __construct(
    public Greeting $greeting,
  )
  {
  }
}

