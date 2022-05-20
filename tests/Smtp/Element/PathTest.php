<?php

namespace Tap\Smtp\Test;

use PHPUnit\Framework\TestCase;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\OriginDomain;
use Tap\Smtp\Element\ReversePath;

class PathTest extends TestCase
{
  public function testReversePath()
  {
    $mailbox = new Mailbox('🦆', new OriginDomain('ducks.com'));
    $path = new ReversePath($mailbox);
    $this->assertFalse($path->isNull());
    $this->assertTrue((new ReversePath(null))->isNull());
  }
}

