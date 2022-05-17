<?php declare(strict_types=1);

namespace Tap\Smtp;

use Tap\App;
use Tap\Smtp\Middleware\Smtp;
use Tap\Tappable;

class Client implements Tappable
{
  use App;

  public function __construct(
    public Smtp $smtp = new Smtp(),
  )
  {
    $this->tap(
      $this->smtp
    );
  }
}

