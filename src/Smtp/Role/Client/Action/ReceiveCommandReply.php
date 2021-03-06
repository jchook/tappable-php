<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Role\Agent\Action\CommandReplyAction;

class ReceiveCommandReply extends CommandReplyAction
{
  public function __construct(
    public Command $command,
    public Reply $reply
  )
  {
  }
}

