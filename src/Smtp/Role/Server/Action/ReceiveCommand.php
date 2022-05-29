<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Role\Agent\Action\CommandAction;

class ReceiveCommand extends CommandAction
{
  public function __construct(
    public Command $command
  )
  {
  }
}

