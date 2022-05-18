<?php

namespace Tap;

interface Tap
{
  public function __invoke(Action $action);
  public function bindTap(callable $dispatch, callable $next): void;
}

