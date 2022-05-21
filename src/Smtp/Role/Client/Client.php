<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client;

use Tap\Smtp\Role\Client\Middleware\ClientBehavior;
use Tap\Smtp\Support\Session;
use Tap\TappableBase;

class Client extends TappableBase
{
  public function __construct(
    public ClientBehavior $smtp = new ClientBehavior(),
  )
  {
    $this->tap(
      $this->smtp,
    );
  }
}

