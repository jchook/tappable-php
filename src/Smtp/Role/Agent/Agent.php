<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent;

use Tap\TappableBase;

class Agent extends TappableBase
{
  public function __construct(
    ...$taps
  )
  {
    $this->tap(...$taps);
  }
}


