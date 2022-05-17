<?php

namespace Tap\Smtp\Action;

use Tap\Action;
use Tap\Smtp\Element;

class Command
{
  use Action;

  public function __construct(
    public Element\Command $command
  )
  {
  }
}
