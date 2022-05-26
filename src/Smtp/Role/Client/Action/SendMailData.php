<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client\Action;

class SendMailData extends ClientAction
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

