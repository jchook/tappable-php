<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class OriginAddressLiteral implements Origin
{
  public function __construct(
    public string $address
  )
  {
  }
}





