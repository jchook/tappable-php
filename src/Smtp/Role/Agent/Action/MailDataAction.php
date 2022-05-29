<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent\Action;

use Tap\Smtp\Role\Agent\Action\AgentAction;

class MailDataAction extends AgentAction
{
  public function __construct(
    /**
     * @var resource
     */
    public $dataStream,
  )
  {
  }
}


