<?php

namespace Tap;

trait TappableTrait
{
  /**
   * @var Tap[]
   */
  private array $taps = [];

  /**
   * @param Tap[] $tap
   */
  public function tap(...$taps): void
  {
    $this->taps = array_merge($this->taps, $taps);
    $this->bindTaps();
  }

  /**
   * Dispatch an action
   */
  public function dispatch(Action $action): void
  {
    if (isset($this->taps[0])) {
      ($this->taps[0])($action);
    }
  }

  /**
   * Binds the tap for $this->next() and $this->dispatch()
   */
  private function bindTaps(): void
  {
    $taps = $this->taps;
    $count = count($taps);
    $emptyNext = function() {};
    for ($idx = 0; $idx < $count; $idx++) {
      $next = $taps[$idx + 1] ?? $emptyNext;
      $taps[$idx]->bindTap([$this, 'dispatch'], $next);
    }
  }
}

