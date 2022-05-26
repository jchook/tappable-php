<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Session\Session;

class NewSession extends ServerAction
{
  public function __construct(
    public Session $session,
  )
  {
  }
}

