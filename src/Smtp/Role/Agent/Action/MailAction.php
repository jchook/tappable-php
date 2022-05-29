<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent\Action;

use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Role\Agent\Action\AgentAction;

class MailAction extends AgentAction
{
  public function __construct(
    public ReversePath $reversePath,
    /**
     * @var RcptTo[]
     */
    public array $forwardPaths,
    /**
     * @var resource
     */
    public $dataStream,
  )
  {
  }
}

