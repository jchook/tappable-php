<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\ActionBase;
use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Support\Transaction;

class SendCommand extends ActionBase
{
  public function __construct(
    public Transaction $txn,
    public Command $command
  )
  {
  }
}

