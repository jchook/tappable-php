<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Role\Agent\Action\MailAction;

class SendMail extends MailAction
{
  public function __construct(
    public ReversePath $reversePath,
    public array $forwardPaths,
    /**
     * @var resource
     */
    public $dataStream,
  )
  {
  }
}

