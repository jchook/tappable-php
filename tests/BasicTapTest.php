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
  public ?float $invokedAt = null;
  public ?Action $invokedWith = null;
  public function __construct(public string $name) {}
  public function __invoke(Action $action)
  {
    $this->invokedAt = microtime(true);
    $this->invokedWith = $action;
    if ($action instanceof MyBasicAction) {
      // echo "\nINVO {$this->name}({$action->name})\n";
      if ($action->dispatchMe) {
        $disp = $action->dispatchMe;
        // echo "\nDISP {$this->name}({$action->name}) -> {$disp->name}\n";
        $disp->dispatchedAt = microtime(true);
        $disp->dispatchedCount++;
        $this->dispatch($disp);
      }
    }
    usleep(10);
    $this->next($action);
  }
}

class MyBasicTappable implements Tappable
{
  use App;
}

class MyBasicAction implements Action
{
  public ?float $dispatchedAt = null;
  public int $dispatchedCount = 0;
  public function __construct(
    public string $name,
    public ?Action $dispatchMe = null
  )
  {
  }
}

class BasicTapTest extends TestCase
{
  public function testBasicTapNext()
  {
    $act = new MyBasicAction('act1');
    $t1 = new MyBasicTap('t1');
    $t2 = new MyBasicTap('t2');
    $src = new MyBasicTappable();
    $src->tap($t1, $t2);
    $src->dispatch($act);
    $this->assertNotEmpty($t1->invokedAt);
    $this->assertSame($act, $t1->invokedWith);
    $this->assertSame($act, $t2->invokedWith);
    $this->assertGreaterThan($t1->invokedAt, $t2->invokedAt);
  }

  public function testBasicTapDispatch()
  {
    $disp = new MyBasicAction('disp');
    $act = new MyBasicAction('act', $disp);
    $t1 = new MyBasicTap('t1');
    $t2 = new MyBasicTap('t2');
    $src = new MyBasicTappable();
    $src->tap($t1, $t2);
    $src->dispatch($act);
    $this->assertNotEmpty($disp->dispatchedAt);
  }
}
