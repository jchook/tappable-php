<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Support\Transaction;

class SendMail extends ClientAction
{
  public function __construct(
    public Transaction $txn,
    public ReversePath $reversePath,
    public array $forwardPaths,
    public $dataStream,
  )
  {
  }
}


