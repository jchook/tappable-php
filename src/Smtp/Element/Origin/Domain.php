<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Origin;

class Domain implements Origin
{
  public function __construct(
    public string $domain
  )
  {
  }
}




