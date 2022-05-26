<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Element\Reply\Greeting;

class SendGreeting extends ServerAction
{
  public function __construct(
    public Greeting $greeting,
  )
  {
  }
}

