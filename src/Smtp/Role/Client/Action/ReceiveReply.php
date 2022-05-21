<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\ActionBase;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Support\Transaction;

class ReceiveReply extends ActionBase
{
  public function __construct(
    public Transaction $txn,
    public Reply $command
  )
  {
  }
}


