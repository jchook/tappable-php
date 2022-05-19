<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;


trait CommandTrait
{
  private Verb $verb;
  public function getVerb(): Verb
  {
    return $this->verb;
  }
}


