<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Reply\Reply;

class ReceiveCommandReply extends ClientAction
{
  public function __construct(
    public Command $command,
    public Reply $reply
  )
  {
  }
}

