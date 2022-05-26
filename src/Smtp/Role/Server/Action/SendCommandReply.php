<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Reply\Reply;

class SendCommandReply extends ServerAction
{
  public function __construct(
    public Command $command,
    public Reply $reply,
  )
  {
  }
}

