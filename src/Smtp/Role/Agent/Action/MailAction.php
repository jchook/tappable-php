<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent\Action;

use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Role\Agent\Action\AgentAction;

class MailAction extends AgentAction
{
  public function __construct(
    public ReversePath $reversePath,
    /**
     * @var ForwardPath[]
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

