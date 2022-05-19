<?php declare(strict_types=1);

namespace Tap\Smtp;

use Tap\Smtp\Middleware\Smtp;
use Tap\TappableBase;

class Client extends TappableBase
{
  public function __construct(
    public Smtp $smtp = new Smtp(),
  )
  {
    $this->tap(
      $this->smtp
    );
  }
}

