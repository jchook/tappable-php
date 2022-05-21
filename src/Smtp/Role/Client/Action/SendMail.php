<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Support\Session;

class SendMail extends ClientAction
{
  public function __construct(
    public Session $txn,
    public ReversePath $reversePath,
    public array $forwardPaths,
    public $dataStream,
  )
  {
  }
}


