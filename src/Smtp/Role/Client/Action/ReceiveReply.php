<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\Reply\Reply;

class ReceiveReply extends ClientAction
{
  public function __construct(
    public Reply $reply
  )
  {
  }
}

