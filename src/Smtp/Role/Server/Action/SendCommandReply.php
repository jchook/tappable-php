<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Role\Agent\Action\CommandReplyAction;

class SendCommandReply extends CommandReplyAction
{
  public function __construct(
    public Command $command,
    public Reply $reply,
  )
  {
  }
}

