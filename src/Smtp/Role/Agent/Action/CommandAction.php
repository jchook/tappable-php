<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent\Action;

use Tap\Smtp\Element\Command\Command;

class CommandAction extends AgentAction
{
  public function __construct(
    public Command $command,
  )
  {
  }
}


