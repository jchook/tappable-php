<?php

namespace Tap;

interface AppInterface
{

  /**
   * @param Middleware[] $middleware
   */
  public function use(...$middlewares);

  /**
   * Dispatch an action
   */
  public function dispatch(Action $action): void;
}
