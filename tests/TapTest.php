<?php

namespace Tap\Tests;

use PHPUnit\Framework\TestCase;
use Tap\Action;
use Tap\ActionBase;
use Tap\ReflectiveTap;
use Tap\TapBase;
use Tap\TappableBase;

class MyPassthruTap extends TapBase
{
}

class MyDispatchTap extends TapBase
{
  public ?float $invokedAt = null;
  public ?Action $invokedWith = null;
  public function __construct(public string $name) {}
  public function __invoke(Action $action)
  {
    $this->invokedAt = microtime(true);
    $this->invokedWith = $action;
    if ($action instanceof MyDispatchAction) {
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

class MyReflectiveTap extends ReflectiveTap
{
  public int $dispatchHandled = 0;
  public int $otherHandled = 0;
  protected array $skipActionHandlers = [
    'dontHandleSkipped',
  ];
  public function dontHandleSkipped(MyOtherAction $action)
  {
    $action;
  }
  public function dontHandleNonActions(MyNonAction $nonAction)
  {
    $nonAction;
  }
  public function dontHandleUnionTypes(MyDispatchAction|null $nonAction)
  {
    $nonAction;
  }
  public function handleMyOtherAction(MyOtherAction $action)
  {
    $this->otherHandled++;
    return $this->next($action);
  }

  public function handleMyDispatchAction(MyDispatchAction $action)
  {
    $this->dispatchHandled++;
    return $this->next($action);
  }
  public function handleMyDispatchAction2(MyDispatchAction $action)
  {
    $this->dispatchHandled += 42;
    return $this->next($action);
  }

}

class MyGenericReflectiveTap extends ReflectiveTap
{
}

class MyTappable extends TappableBase
{
}

class MyOtherAction extends ActionBase
{
}

class MyDispatchAction extends ActionBase
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

class MyExtendedAction extends MyDispatchAction
{
}

class MyNonAction {}

class TapTest extends TestCase
{
  public function testMyTapCallsNext()
  {
    $act = new MyDispatchAction('act1');
    $t1 = new MyDispatchTap('t1');
    $t2 = new MyPassthruTap();
    $t3 = new MyDispatchTap('t3');
    $src = new MyTappable();
    $src->tap($t1, $t2, $t3);
    $src->dispatch($act);
    $this->assertNotEmpty($t1->invokedAt);
    $this->assertSame($act, $t1->invokedWith);
    $this->assertSame($act, $t3->invokedWith);
    $this->assertGreaterThan($t1->invokedAt, $t3->invokedAt);
  }

  public function testMyTapCallsDispatch()
  {
    $disp = new MyDispatchAction('disp');
    $act = new MyDispatchAction('act', $disp);
    $t1 = new MyDispatchTap('t1');
    $t2 = new MyDispatchTap('t2');
    $src = new MyTappable();
    $src->tap($t1, $t2);
    $src->dispatch($act);
    $this->assertNotEmpty($disp->dispatchedAt);
  }

  public function testMyReflectiveTapCallsTheCorrectHandler()
  {
    $extended = new MyExtendedAction('extended');
    $t1 = new MyReflectiveTap();
    $src = new MyTappable();
    $src->tap($t1);
    $src->dispatch($extended);
    $this->assertEquals(
      1,
      $t1->dispatchHandled,
      'ReflectiveTap handles extended action types'
    );
    $this->assertEquals(0, $t1->otherHandled);
    $other = new MyOtherAction();
    $src->dispatch($other);
    $this->assertEquals(1, $t1->otherHandled);
    $this->assertEquals(1, $t1->dispatchHandled);
  }

  // public function testMyGenericReflectiveTap()
  // {
  //   $t1 = new MyGenericReflectiveTap();
  //   $t1(new MyOtherAction());
  // }
}
