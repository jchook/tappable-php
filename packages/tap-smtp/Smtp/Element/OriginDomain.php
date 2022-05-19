<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class OriginDomain implements Origin
{
  public function __construct(
    public string $domain
  )
  {
  }
}




