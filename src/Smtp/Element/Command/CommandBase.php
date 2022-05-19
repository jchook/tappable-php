<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;


class CommandBase implements Command
{
  public string $verb = '';
  public function getVerb(): string
  {
    return $this->verb;
  }
}


