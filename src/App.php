<?php

namespace Tap;

trait App
{
  /**
   * @var Middleware[]
   */
  private array $middlewares = [];

  /**
   * @param Middleware[] $middleware
   */
  public function use(...$middlewares)
  {
    $this->middlewares = array_merge($this->middlewares, $middlewares);
    $this->bindMiddleware();
  }

  /**
   * Dispatch an action
   */
  public function dispatch(ActionInterface $action): void
  {
    if (isset($this->middlewares[0])) {
      ($this->middlewares[0])($action);
    }
  }

  /**
   * Binds the middleware for $this->next() and $this->dispatch()
   */
  private function bindMiddleware(): void
  {
    $middlewares = $this->middlewares;
    $idx = 0;
    // IDEA:
    // if this next function knew which actions each middleware supported, it
    // could avoid calling irrelevant middleware, and cache per-action lists of
    // middleware.
    $next = function(ActionInterface $action) use ($middlewares, &$idx) {
      $idx++;
      $middlewares[$idx]($action);
    };
    foreach ($middlewares as $middleware) {
      // Previously I really really wanted to support PHP anonymous functions
      // and the classic Redux function signature. I think it's time to let that
      // idea go. PHP is not JS.
      $middleware->bindAppAndNext($this, $next);
    }
  }
}
