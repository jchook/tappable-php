<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\Reply\Greeting;

class ReceiveGreeting extends ClientAction
{
  public function __construct(
    public Greeting $greeting,
  )
  {
  }
}


