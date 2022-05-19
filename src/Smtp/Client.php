<?php declare(strict_types=1);

namespace Tap\Smtp;

use Tap\Smtp\Middleware\Transactional;
use Tap\TappableBase;

class Client extends TappableBase
{
  public function __construct(
    public Transactional $transactional = new Transactional(),
  )
  {
    $this->tap(
      $this->transactional
    );
  }
}

