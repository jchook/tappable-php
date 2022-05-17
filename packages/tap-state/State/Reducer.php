<?php

namespace Tap\State;

use Tap\Action;

interface Reducer
{
  public function reduce($state, Action $action);
}

