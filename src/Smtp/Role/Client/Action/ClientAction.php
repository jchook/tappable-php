<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\ActionBase;
use Tap\Smtp\Support\Session;

class ClientAction extends ActionBase
{
  public function __construct(
    public Session $txn,
  )
  {
  }
}



