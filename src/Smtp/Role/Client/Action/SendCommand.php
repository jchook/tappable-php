<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Role\Agent\Action\CommandAction;

class SendCommand extends CommandAction
{
  public function __construct(
    public Command $command
  )
  {
  }
}

