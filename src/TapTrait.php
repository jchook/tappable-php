<?php

namespace Tap;

/**
 * Minimal implementation of a Tap
 */
trait TapTrait
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
    return $this->source->dispatch($action);
  }
}

