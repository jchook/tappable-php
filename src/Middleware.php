<?php

namespace Tap;

trait Middleware
{
  private callable $dispatch;
  private callable $next;

  protected function bindDispatchAndNext(callable $dispatch, callable $next): void
  {
    $this->dispatch = $dispatch;
    $this->next = $next;
  }

  protected function next(Action $action): void
  {
    ($this->next)($action);
  }

  protected function dispatch(Action $action): void
  {
    ($this->dispatch)($action);
  }
}

