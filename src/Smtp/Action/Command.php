<?php

namespace Tap\Smtp\Action;

use Tap\ActionInterface;
use Tap\Smtp\Element;

class Command implements ActionInterface
{
  public function __construct(
    public Element\Command $command
  )
  {
  }
}
