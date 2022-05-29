<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent\Action;

use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Reply\Reply;

class CommandReplyAction extends AgentAction
{
  public function __construct(
    public Command $command,
    public Reply $reply,
  )
  {
  }
}

