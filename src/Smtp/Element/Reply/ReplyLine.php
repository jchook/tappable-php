<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Reply;

class ReplyLine
{
  public function __construct(
    public Code $code,
    public string $message,
    public bool $continue,
  )
  {
  }
}

