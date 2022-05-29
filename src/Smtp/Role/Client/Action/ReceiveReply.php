<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Role\Agent\Action\AgentAction;

class ReceiveReply extends AgentAction
{
  public function __construct(
    public Reply $reply
  )
  {
  }
}

