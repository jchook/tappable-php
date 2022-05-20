<?php

namespace Tap\State;

use Tap\Action;

interface Reducer
{
  public function __invoke($state, Action $action);
}

