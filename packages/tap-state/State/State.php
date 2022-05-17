<?php

namespace Tap\State;

use Tap\Action;
use Tap\BasicTap;
use Tap\Tap;

class State implements Tap
{
  use BasicTap;

  public function __construct(
    protected Reducer $reducer,
    protected $state = null,
  )
  {
  }

  public function __invoke(Action $action)
  {
    $this->state = $this->reducer->reduce($this->state, $action);
  }

  public function replaceReducer(Reducer $reducer): void
  {
    $this->reducer = $reducer;
  }

  public function getState()
  {
    return $this->state;
  }
}
