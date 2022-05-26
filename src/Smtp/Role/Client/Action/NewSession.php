<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\ActionBase;
use Tap\Smtp\Session\Session;

class NewSession extends ClientAction
{
  public function __construct(
    public Session $session,
  )
  {
  }
}

