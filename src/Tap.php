<?php

namespace Tap;

interface Tap
{
  public function __invoke(Action $action);
  public function bindTap(Tappable $source, callable $next): void;
}

