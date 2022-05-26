<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Element\Command\Command;

class ReceiveCommand extends ServerAction
{
  public function __construct(
    public Command $command
  )
  {
  }
}

