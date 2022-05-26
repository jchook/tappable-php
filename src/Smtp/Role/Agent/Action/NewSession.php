<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent\Action;

use Tap\Smtp\Session\Session;

class NewSession extends AgentAction
{
  public function __construct(
    public Session $session,
  )
  {
  }
}

