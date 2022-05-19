<?php

namespace Tap\Smtp\Action;

use Tap\Action;
use Tap\Smtp\Element;
use Tap\Smtp\Support\Transaction;

class Reply implements Action
{
  public function __construct(
    public Transaction $txn,
    public Element\Reply\Reply $reply
  )
  {
  }
}

