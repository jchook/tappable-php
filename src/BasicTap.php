<?php

namespace Tap;

/**
 * Minimal implementation of a Tap
 */
trait BasicTap
{
  private ?Tappable $source = null;

  /**
   * https://github.com/phpDocumentor/phpDocumentor/issues/1712#issuecomment-727865336=
   * @var callable(Action): void
   */
  private $next = null;

  public function bindTap(Tappable $source, callable $next): void
  {
    $this->source = $source;
    $this->next = $next;
  }

  /**
   * Override me
   */
  public function __invoke(Action $action): void
  {
    $this->next($action);
  }

  protected function next(Action $action): void
  {
    ($this->next)($action);
  }

  protected function dispatch(Action $action): void
  {
    $this->source->dispatch($action);
  }
}

