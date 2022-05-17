<?php declare(strict_types=1);

namespace Tap\Smtp;

use Tap\App;
use Tap\AppInterface;

class Client implements AppInterface
{
  use App;

  public function __construct(
    public Smtp $smtp = new Smtp(),
  )
  {
  }
}

