<?php

namespace Tap\Tests;

use PHPUnit\Framework\TestCase;
use Tap\Action;
use Tap\App;
use Tap\BasicTap;
use Tap\Tap;
use Tap\Tappable;

class MyBasicTap implements Tap
{
  use BasicTap;
  public ?int $invokedAt = null;
  public ?Action $invokedWith = null;
  public function __invoke(Action $action)
  {
    $this->invokedAt = microtime(true);
    $this->invokedWith = $action;
    $this->next($action);
  }
}

class MyBasicTappable implements Tappable
{
  use App;
}

class MyBasicAction implements Action
{
}

class BasicTapTest extends TestCase
{
  public function testBasicTap()
  {
    $t1 = new MyBasicTap();
    $src = new MyBasicTappable();
    $src->tap($t1);
    $act = new MyBasicAction();
    $src->dispatch($act);
    $this->assertNotEmpty($t1->invokedAt);
    $this->assertSame($act, $t1->invokedWith);
  }
}
