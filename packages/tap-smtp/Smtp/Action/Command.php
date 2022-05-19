<?php

namespace Tap\Smtp\Action;

use Tap\Action;
use Tap\Smtp\Element;
use Tap\Smtp\Support\Transaction;

class Command implements Action
{
  public function __construct(
    public Transaction $txn,
    public Element\Command\Command $command
  )
  {
  }
}
