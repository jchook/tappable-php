<?php

namespace Tap;

interface Tappable
{
  /**
   * @param Tap[] $taps
   */
  public function tap(...$taps): void;

  /**
   * Dispatch an action
   */
  public function dispatch(Action $action);
}
