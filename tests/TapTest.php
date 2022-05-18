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
  public int $dispatchActionHandled = 0;
  public int $relatedActionHandeled = 0;
  protected array $skipActionHandlers = [
    'dontHandleSkipped',
  ];
  public function dontHandleSkipped(MyRelatedAction $action)
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
  public function handleMyRelatedAction(MyRelatedAction $action)
  {
    $this->relatedActionHandeled++;
    return $this->next($action);
  }

  public function handleMyDispatchAction(MyDispatchAction $action)
  {
    $this->dispatchActionHandled++;
    return $this->next($action);
  }
  public function handleMyDispatchAction2(MyDispatchAction $action)
  {
    $this->dispatchActionHandled += 42;
    return $this->next($action);
  }

}

class MyGenericReflectiveTap extends ReflectiveTap
{
}

class MyTappable extends TappableBase
{
}

class MyRelatedAction extends ActionBase
{
}

class MyUnrelatedAction extends ActionBase
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
    $extendedAction = new MyExtendedAction('extendedAction');
    $t1 = new MyReflectiveTap();
    $src = new MyTappable();
    $src->tap($t1);
    $src->dispatch($extendedAction);
    $this->assertEquals(
      1,
      $t1->dispatchActionHandled,
      'ReflectiveTap handles MyExtendedAction action types'
    );
    $this->assertEquals(0, $t1->relatedActionHandeled);
    $relatedAction = new MyRelatedAction();
    $src->dispatch($relatedAction);
    $this->assertEquals(1, $t1->relatedActionHandeled);
    $this->assertEquals(1, $t1->dispatchActionHandled);

    // Testing the inheritance cache
    // it's really unnnecessary but works!
    $unrelatedAction = new MyUnrelatedAction();
    $src->dispatch($unrelatedAction);
    $src->dispatch($unrelatedAction);
  }

  public function testMyGenericReflectiveTap()
  {
    $nextCalled = 0;
    $dispatch = function() {};
    $next = function() use (&$nextCalled) { $nextCalled += 1; };
    $t1 = new MyGenericReflectiveTap();
    $t1->bindTap($dispatch, $next);
    $t1(new MyRelatedAction());
    $this->assertSame(1, $nextCalled);
  }
}
