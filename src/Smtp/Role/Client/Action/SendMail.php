<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

use Tap\Smtp\Element\ReversePath;

class SendMail extends ClientAction
{
  public function __construct(
    public ReversePath $reversePath,
    public array $forwardPaths,
    public $dataStream,
  )
  {
  }
}


