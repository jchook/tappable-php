<?php

namespace Tap;

trait App
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
    $idx = 0;
    // IDEA:
    // if this next function knew which actions each tap supported, it
    // could avoid calling irrelevant tap, and cache per-action lists of
    // tap.
    $next = function(Action $action) use ($taps, &$idx) {
      $idx++;
      if (isset($taps[$idx])) {
        return $taps[$idx]($action);
      }
    };
    foreach ($taps as $tap) {
      // Previously I really really wanted to support PHP anonymous functions
      // and the classic Redux function signature. I think it's time to let that
      // idea go. PHP is not JS.
      $tap->bindTap($this, $next);
    }
  }
}
