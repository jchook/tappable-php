<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\ActionBase;
use Tap\Smtp\Element\Command\Command;

class SendCommand extends ActionBase
{
  public function __construct(
    public Command $command
  )
  {
  }
}

