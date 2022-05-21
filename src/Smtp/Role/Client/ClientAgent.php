<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client;

use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Role\Agent\Agent;
use Tap\Smtp\Role\Client\Middleware\ClientBehavior;
use Tap\Smtp\Support\System;

class ClientAgent extends Agent
{
  public ClientBehavior $smtp;
  public Origin $origin;

  public function __construct(
    ?Origin $origin = null,
    ?ClientBehavior $smtp = null,
  )
  {
    $this->origin = $origin ?? System::getHostDomain();
    $this->smtp = $smtp ?? new ClientBehavior($this->origin);
    $this->tap(
      $this->smtp,
    );
  }
}

