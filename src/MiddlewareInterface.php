<?php

namespace Tap;

interface MiddlewareInterface
{
  private App $app;
  private callable $next;

  public function __invoke(ActionInterface $action): void;
  public function bindAppAndNext(App $app, callable $next): void;
}

