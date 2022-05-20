<?php

namespace Tap\State;

use Tap\Action;
use Tap\TapBase;

class Stateful implements TapBase
{
  public function __construct(
    protected callable $reducer,
    protected $state = null,
  )
  {
  }

  public function __invoke(Action $action)
  {
    $this->state = ($this->reducer)($this->state, $action);
  }

  public function replaceReducer(callable $reducer): void
  {
    $this->reducer = $reducer;
  }

  public function getState()
  {
    return $this->state;
  }
}
