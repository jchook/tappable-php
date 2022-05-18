<?php

namespace Tap;

/**
 * Minimal implementation of a Tap
 */
trait TapTrait
{
  /**
   * https://github.com/phpDocumentor/phpDocumentor/issues/1712#issuecomment-727865336=
   * @var callable(Action): void
   */
  private $dispatch = null;

  /**
   * https://github.com/phpDocumentor/phpDocumentor/issues/1712#issuecomment-727865336=
   * @var callable(Action): void
   */
  private $next = null;

  public function bindTap(callable $dispatch, callable $next): void
  {
    $this->dispatch = $dispatch;
    $this->next = $next;
  }

  /**
   * Override me
   */
  public function __invoke(Action $action)
  {
    return $this->next($action);
  }

  protected function next(Action $action)
  {
    return ($this->next)($action);
  }

  protected function dispatch(Action $action)
  {
    return ($this->dispatch)($action);
  }
}

