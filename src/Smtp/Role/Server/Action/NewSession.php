<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Role\Agent\Action\NewSession as AgentNewSession;
use Tap\Smtp\Session\Session;

class NewSession extends AgentNewSession
{
  public function __construct(
    public Session $session,
  )
  {
  }
}

